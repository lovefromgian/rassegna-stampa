// Cattura di una pagina web per la rassegna stampa.
// Invocato dal job Laravel (App\Support\Capture\PlaywrightCapturer), mai a mano.
//
//   node capture.js --url=<URL> --out=<CARTELLA>
//
// Produce nella cartella di output: screenshot.png (full-page), page.pdf (multipagina),
// text.txt (testo estratto). Stampa su stdout un JSON di metadati:
//   { ok: true, title, finalUrl }           in caso di successo
//   { ok: false, error }                     in caso di errore (+ exit 1)
//
// Gestisce: cookie banner diffusi in Italia (Iubenda, OneTrust, generici), scroll dei
// contenuti lazy, attesa networkidle, blocco dei domini pubblicitari (cattura più pulita).

const fs = require('fs');
const path = require('path');

function arg(nome) {
  const p = process.argv.find((a) => a.startsWith(`--${nome}=`));
  return p ? p.slice(nome.length + 3) : null;
}

// Domini pubblicitari / di tracciamento da bloccare per una cattura più pulita e veloce.
const DOMINI_BLOCCATI = [
  'doubleclick.net', 'googlesyndication.com', 'googletagmanager.com',
  'google-analytics.com', 'adservice.google', 'adnxs.com', 'criteo',
  'taboola.com', 'outbrain.com', 'facebook.net', 'connect.facebook',
  'amazon-adsystem.com', 'pubmatic.com', 'rubiconproject.com', 'scorecardresearch',
];

// Selettori di consenso cookie più comuni (accetta/chiudi).
const SELETTORI_CONSENSO = [
  '#iubenda-cs-accept-btn',
  '.iubenda-cs-accept-btn',
  '#onetrust-accept-btn-handler',
  '.ot-sdk-container #onetrust-accept-btn-handler',
  '[aria-label="Accetta"]',
  'button#accept',
  '.fc-cta-consent',
  '.cookie-accept',
];

async function chiudiConsenso(page) {
  for (const sel of SELETTORI_CONSENSO) {
    try {
      const el = await page.$(sel);
      if (el) {
        await el.click({ timeout: 2000 });
        await page.waitForTimeout(400);
        return;
      }
    } catch (_) { /* prova il prossimo */ }
  }
  // Fallback: bottone con testo tipico.
  for (const testo of ['Accetta', 'Accetto', 'Accept', 'Acconsento', 'OK']) {
    try {
      const btn = page.getByRole('button', { name: new RegExp(`^\\s*${testo}`, 'i') }).first();
      if (await btn.count()) {
        await btn.click({ timeout: 2000 });
        await page.waitForTimeout(400);
        return;
      }
    } catch (_) { /* ignora */ }
  }
}

// Un redirect (tipico degli URL Google News: la pagina d'atterraggio rimanda
// all'articolo vero via JS) distrugge il contesto di esecuzione durante una evaluate.
// Aspetta che l'URL smetta di cambiare prima di lavorare sulla pagina.
async function attendiStabilizzazione(page, timeoutMs = 25000) {
  const scadenza = Date.now() + timeoutMs;
  let urlPrec = page.url();
  let fermi = 0;
  while (Date.now() < scadenza) {
    await page.waitForTimeout(1000);
    const urlOra = page.url();
    if (urlOra === urlPrec) {
      if (++fermi >= 2) break; // due giri senza cambi: consideriamo assestato
    } else {
      fermi = 0;
      urlPrec = urlOra;
      try { await page.waitForLoadState('domcontentloaded', { timeout: 6000 }); } catch (_) {}
    }
  }
  try { await page.waitForLoadState('networkidle', { timeout: 8000 }); } catch (_) {}
}

function isNavigazione(err) {
  const m = String(err && err.message ? err.message : err);
  return m.includes('Execution context was destroyed') || m.includes('context was destroyed');
}

// Esegue una evaluate tollerando una navigazione tardiva: se il contesto viene
// distrutto, riassesta la pagina e riprova una volta.
async function evaluateResiliente(page, fn) {
  try {
    return await page.evaluate(fn);
  } catch (err) {
    if (!isNavigazione(err)) throw err;
    await attendiStabilizzazione(page, 10000);
    return await page.evaluate(fn);
  }
}

async function scrollFinoInFondo(page) {
  await evaluateResiliente(page, async () => {
    await new Promise((resolve) => {
      let totale = 0;
      const passo = 400;
      const timer = setInterval(() => {
        window.scrollBy(0, passo);
        totale += passo;
        if (totale >= document.body.scrollHeight) {
          clearInterval(timer);
          resolve();
        }
      }, 100);
    });
  });
  await evaluateResiliente(page, () => window.scrollTo(0, 0));
  await page.waitForTimeout(500);
}

(async () => {
  const url = arg('url');
  const out = arg('out');

  if (!url || !out) {
    console.log(JSON.stringify({ ok: false, error: 'Argomenti mancanti: --url e --out sono obbligatori.' }));
    process.exit(1);
  }

  let browser;
  try {
    const { chromium } = require('playwright');
    browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
    const context = await browser.newContext({
      locale: 'it-IT',
      viewport: { width: 1366, height: 900 },
      userAgent:
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
    });

    // Blocco domini pubblicitari.
    await context.route('**/*', (route) => {
      const u = route.request().url();
      if (DOMINI_BLOCCATI.some((d) => u.includes(d))) {
        return route.abort();
      }
      return route.continue();
    });

    const page = await context.newPage();
    // 'domcontentloaded' (non 'networkidle'): con gli URL Google News la pagina
    // d'atterraggio redirige subito e 'networkidle' fa attendere/fallire. Ci si
    // assesta subito dopo, aspettando che i redirect si esauriscano.
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await attendiStabilizzazione(page);

    await chiudiConsenso(page);
    await attendiStabilizzazione(page, 8000); // il consenso può innescare un'altra navigazione
    await scrollFinoInFondo(page);

    // timeout ampio (default 30s): pagine con font lenti o molto lunghe superavano i 30s
    // "waiting for fonts to load"; il budget del job (CAPTURE_TIMEOUT) resta il tetto.
    await page.screenshot({ path: path.join(out, 'screenshot.png'), fullPage: true, timeout: 60000, animations: 'disabled' });

    try {
      await page.pdf({ path: path.join(out, 'page.pdf'), printBackground: true, format: 'A4', timeout: 60000 });
    } catch (_) { /* page.pdf solo in headless: se fallisce, si prosegue senza */ }

    let testo = '';
    try { testo = await evaluateResiliente(page, () => document.body.innerText || ''); } catch (_) { testo = ''; }
    fs.writeFileSync(path.join(out, 'text.txt'), testo, 'utf8');

    const title = await page.title();
    const finalUrl = page.url();

    console.log(JSON.stringify({ ok: true, title, finalUrl }));
    await browser.close();
    process.exit(0);
  } catch (err) {
    if (browser) { try { await browser.close(); } catch (_) {} }
    console.log(JSON.stringify({ ok: false, error: String(err && err.message ? err.message : err) }));
    process.exit(1);
  }
})();
