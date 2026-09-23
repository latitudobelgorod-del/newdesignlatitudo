/*
 * Проверка урезанной сборки: первый экран «как было» (?nd_nopurge=1) против «только урезанная копия»
 * (полная сборка заблокирована — худший случай, пока она не догрузилась). Попиксельное сравнение.
 *   node compare.js http://newdesign-latitudo   → проценты отличий, снимки отличий в ./shots
 */
const fs = require('fs');
const puppeteer = require('puppeteer-core');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch').default || require('pixelmatch');

const BASE = (process.argv[2] || 'http://newdesign-latitudo').replace(/\/$/, '');
const Q = BASE.includes('latitudo.ru') ? '' : 'newdesign=Y';
const CHROME = process.env.CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PAGES = ['/', '/catalog/', '/catalog/terrasnaya-doska-iz-dpk/', '/catalog/sadovaya-mebel/kresla/',
	'/catalog/sadovaya-mebel/kresla/kreslo-nova-irbis/', '/basket/', '/contacts/', '/projects/', '/services/', '/company/reviews/'];
const VIEWPORTS = [[360, 800, true], [1440, 900, false]];
const u = (p, extra) => BASE + p + '?' + [Q, extra].filter(Boolean).join('&');

(async () => {
	fs.mkdirSync(__dirname + '/shots', { recursive: true });
	const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new' });
	const shot = async (url, w, h, mob, blockFull) => {
		const page = await browser.newPage();
		await page.setViewport({ width: w, height: h, isMobile: mob, hasTouch: mob });
		if (blockFull) {
			await page.setRequestInterception(true);
			page.on('request', r => /\/bitrix\/cache\/css\/s1\/aspro_next_newdesign\/template_/.test(r.url()) ? r.abort() : r.continue());
		}
		await page.goto(url, { waitUntil: 'networkidle2', timeout: 180000 }).catch(() => {});
		await page.evaluate(() => document.querySelectorAll('#cb-wrap').forEach(e => e.remove())).catch(() => {});
		await new Promise(r => setTimeout(r, 800));
		const buf = await page.screenshot({ type: 'png' });
		await page.close();
		return PNG.sync.read(buf);
	};
	for (const [w, h, mob] of VIEWPORTS) {
		for (const p of PAGES) {
			const a = await shot(u(p, 'nd_nopurge=1'), w, h, mob, false);
			const b = await shot(u(p, ''), w, h, mob, true);
			const W = Math.min(a.width, b.width), H = Math.min(a.height, b.height);
			const crop = img => { const o = new PNG({ width: W, height: H }); PNG.bitblt(img, o, 0, 0, W, H, 0, 0); return o; };
			const A = crop(a), B = crop(b), D = new PNG({ width: W, height: H });
			const n = pixelmatch(A.data, B.data, D.data, W, H, { threshold: 0.15 });
			const pct = (n / (W * H) * 100).toFixed(2);
			const name = `${w}${p.replace(/[^a-z0-9]+/gi, '_')}`;
			if (n > W * H * 0.005) { fs.writeFileSync(`${__dirname}/shots/${name}-A.png`, PNG.sync.write(A)); fs.writeFileSync(`${__dirname}/shots/${name}-B.png`, PNG.sync.write(B)); fs.writeFileSync(`${__dirname}/shots/${name}-diff.png`, PNG.sync.write(D)); }
			console.log(`${String(pct).padStart(6)}%  ${w}px  ${p}`);
		}
	}
	await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
