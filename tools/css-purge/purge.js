/*
 * Урезанная копия CSS-сборки шаблона нового дизайна (22.09.2026).
 *
 * Сборка /bitrix/cache/css/s1/aspro_next_newdesign/template_<hash>/template_<hash>_v1.css весит ~1,5 МБ
 * (старая тема Аспро + newdesign*.css), а на страницах нового дизайна срабатывает ~12% её правил.
 * Скрипт открывает страницы в Chrome (телефон и компьютер), для каждого правила сборки проверяет,
 * есть ли на странице подходящий элемент (включая скрытые меню и шторки), и пишет только нужные правила в
 *   local/templates/aspro_next_newdesign/css/purged/template_<hash>.css
 * Подменяет ссылку на сборку ndPurgedCss() из local/init.php: урезанная копия подключается как обычно,
 * а полная сборка — на том же месте, но без ожидания (media=print → all). Нет копии под текущий <hash> —
 * страница получает полную сборку, как раньше.
 *
 * Запуск (после любой правки CSS шаблона на этом сайте — у сборки меняется <hash>):
 *   cd tools/css-purge && npm i && node purge.js http://newdesign-latitudo      (локально)
 *   node purge.js https://latitudo.ru                                            (прод; копию потом выложить)
 */
const fs = require('fs');
const path = require('path');
const postcss = require('postcss');
const puppeteer = require('puppeteer-core');

const BASE = (process.argv[2] || 'http://newdesign-latitudo').replace(/\/$/, '');
const Q = BASE.includes('latitudo.ru') ? '' : '?newdesign=Y';
const CHROME = process.env.CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const OUT = path.resolve(__dirname, '../../local/templates/aspro_next_newdesign/css/purged');
// Разные типы страниц: у каждого свой набор блоков.
const PAGES = ['/', '/catalog/', '/catalog/terrasnaya-doska-iz-dpk/', '/catalog/sadovaya-mebel/kresla/',
	'/catalog/sadovaya-mebel/kresla/kreslo-nova-irbis/', '/catalog/terrasnaya-doska-iz-dpk/terrasnaya-doska-easydecking/',
	'/basket/', '/order/', '/contacts/', '/projects/', '/projects/besedki/', '/services/', '/sale/', '/brands/',
	'/company/', '/company/reviews/', '/info/', '/search/?q=доска', '/catalog/?q=доска', '/nd-404-check/'];
const VIEWPORTS = [[360, 800, true], [1440, 900, false]];
// Классы, которые скрипты ставят сразу при загрузке (до того, как догрузится полная сборка): их правила
// берём всегда. Состояния от действий (открытое меню, всплывающая форма, hover) сюда не нужны — к первому
// клику полная сборка уже подключена.
const SAFE = [/\.(loaded|show|shown|in|fixed|sticky|lazyloaded|current|active|selected|checked)\b/];

const strip = s => s
	.replace(/::?(before|after|placeholder|selection|first-line|first-letter|marker|backdrop|-webkit-[\w-]+|-moz-[\w-]+|-ms-[\w-]+)(\([^)]*\))?/g, '')
	.replace(/:(hover|focus|focus-within|focus-visible|active|visited|link|checked|disabled|enabled|invalid|valid|required|optional|indeterminate|target|empty|read-only|read-write|placeholder-shown|autofill|-webkit-autofill)\b/g, '')
	.replace(/\s*[>+~]\s*$/, '').trim() || '*';

const url = p => BASE + p + (Q ? (p.includes('?') ? '&' : '?') + Q.slice(1) : '');
const RE_BUNDLE = /href="(\/bitrix\/cache\/css\/s1\/aspro_next_newdesign\/template_([0-9a-f]{32})\/template_\2_v1\.css)[^"]*"/;

(async () => {
	// У каждой страницы своя сборка (компоненты добавляют свои файлы) — собираем все варианты.
	const bundles = {};
	for (const p of PAGES) {
		const m = (await (await fetch(url(p))).text()).match(RE_BUNDLE);
		if (m && !bundles[m[2]]) bundles[m[2]] = { url: m[1], css: await (await fetch(BASE + m[1])).text() };
	}
	if (!Object.keys(bundles).length) throw new Error('не нашла ссылок на сборку template_*.css');
	const sels = new Set();
	for (const [h, b] of Object.entries(bundles)) {
		b.root = require('postcss-safe-parser')(b.css);
		b.root.walkRules(r => { if (r.parent.type !== 'atrule' || !/keyframes/i.test(r.parent.name)) r.selectors.forEach(s => sels.add(s)); });
		console.log(`сборка ${h}: ${Math.round(b.css.length / 1024)} КБ`);
	}
	const list = [...sels];
	const test = list.map(s => ({ s, q: strip(s), safe: SAFE.some(re => re.test(s)) }));
	console.log(`селекторов всего ${list.length}`);

	const used = new Set(test.filter(t => t.safe || t.q === '*').map(t => t.s));
	const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new' });
	for (const [w, h, mob] of VIEWPORTS) {
		for (const p of PAGES) {
			const page = await browser.newPage();
			await page.setViewport({ width: w, height: h, isMobile: mob, hasTouch: mob });
			await page.goto(url(p), { waitUntil: 'networkidle2', timeout: 180000 }).catch(() => {});
			await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight)).catch(() => {});
			await new Promise(r => setTimeout(r, 1200));
			const hit = await page.evaluate(qs => qs.map(q => { try { return !!document.querySelector(q); } catch (e) { return true; } }),
				test.filter(t => !used.has(t.s)).map(t => t.q)).catch(() => []);
			test.filter(t => !used.has(t.s)).forEach((t, i) => { if (hit[i]) used.add(t.s); });
			await page.close();
			process.stdout.write('.');
		}
	}
	await browser.close();
	console.log(`\nиспользуется селекторов ${used.size} из ${list.length}`);

	fs.mkdirSync(OUT, { recursive: true });
	for (const [h, b] of Object.entries(bundles)) {
		b.root.walkRules(r => {
			if (r.parent.type === 'atrule' && /keyframes/i.test(r.parent.name)) return;
			const keep = r.selectors.filter(s => used.has(s));
			if (!keep.length) r.remove(); else if (keep.length !== r.selectors.length) r.selectors = keep;
		});
		let changed = true;
		while (changed) { changed = false; b.root.walkAtRules(a => { if (/^(media|supports|document|-moz-document)$/i.test(a.name) && !(a.nodes || []).length) { a.remove(); changed = true; } }); }
		const out = `/* Урезанная копия ${b.url} — tools/css-purge/purge.js, ${new Date().toISOString()}, ${BASE} */
` + b.root.toString();
		const file = path.join(OUT, `template_${h}.css`);
		fs.writeFileSync(file, out);
		console.log(`${path.basename(file)}: ${Math.round(out.length / 1024)} КБ (было ${Math.round(b.css.length / 1024)} КБ)`);
	}
})().catch(e => { console.error(e); process.exit(1); });
