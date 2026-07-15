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

// Rimuove i banner di consenso/paywall/overlay rimasti dopo il tentativo di "Accetta":
// per parole chiave note (CMP diffusi) e i modali/backdrop a tutto schermo che coprono
// l'articolo. Ripristina lo scroll che i banner spesso bloccano. Best-effort.
async function rimuoviBannerResidui(page) {
  try {
    await page.evaluate(() => {
      const KW = /(cookie|consent|gdpr|privacy|cmp|didomi|onetrust|ot-sdk|iubenda|cookiebot|usercentrics|truste|qc-cmp|sp_message|sourcepoint|osano|cookieyes|borlabs|complianz|termly|__tcfapi|paywall|subscribe|newsletter-modal|gdpr-banner)/i;

      // 1) contenitori che si dichiarano banner di consenso (id/classe/aria/attributi)
      document.querySelectorAll('div,section,aside,dialog,iframe,aside').forEach((el) => {
        const firma = [el.id, el.className, el.getAttribute('aria-label'), el.getAttribute('data-testid'), el.getAttribute('data-cy')]
          .filter(Boolean).join(' ');
        if (typeof firma === 'string' && KW.test(firma)) el.remove();
      });

      // 2) modali/backdrop fissi a tutto schermo che bloccano la lettura (z-index alto,
      //    larghi e alti): quasi sempre consensi/iscrizioni. I piccoli sticky (header,
      //    barre) restano.
      document.querySelectorAll('body *').forEach((el) => {
        const s = getComputedStyle(el);
        if (s.position !== 'fixed' && s.position !== 'sticky') return;
        const r = el.getBoundingClientRect();
        const z = parseInt(s.zIndex, 10) || 0;
        const largo = r.width >= window.innerWidth * 0.8;
        const alto = r.height >= window.innerHeight * 0.5;
        if (z >= 1000 && largo && alto) el.remove();
      });

      // 3) ripristina lo scroll bloccato dal banner
      for (const el of [document.documentElement, document.body]) {
        if (!el) continue;
        el.style.setProperty('overflow', 'auto', 'important');
        el.style.setProperty('position', 'static', 'important');
        el.style.setProperty('filter', 'none', 'important');
      }
    });
  } catch (_) { /* best-effort: se fallisce si prosegue */ }
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
    // --disable-dev-shm-usage: su server con /dev/shm piccolo, lo screenshot full-page
    // di pagine molto lunghe fa crashare il tab ("Target crashed"); usa /tmp al suo posto.
    browser = await chromium.launch({ headless: true, args: ['--no-sandbox', '--disable-dev-shm-usage'] });
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
    await rimuoviBannerResidui(page);         // togli i banner che il click non ha chiuso
    await scrollFinoInFondo(page);
    await rimuoviBannerResidui(page);         // di nuovo: lo scroll può far comparire nuovi banner

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
