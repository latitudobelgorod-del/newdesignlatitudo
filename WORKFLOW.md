# Работа над latitudo.ru с двух машин

Репозиторий: `github.com/latitudobelgorod-del/newdesignlatitudo`
Прод: `latitudo.ru`, хостинг Reg.ru `vip125.hosting.reg.ru`, пользователь `u0916578`, каталог `~/www/latitudo.ru`

> Не путать с **latitudo.pro** — это другой сайт, другой аккаунт (`vip300`, `u3550418`),
> другой репозиторий. Прежде чем что-то писать на прод — сверить хостинг-пользователя.

---

## 1. Ежедневный порядок

```
git pull                  # начиная работу
...правки...
git commit && git push    # заканчивая работу
```

**Главное правило:** не оставлять незакоммиченное на машине, от которой уходишь. Иначе на
второй машине этих правок не будет, а при следующем коммите придётся мержить руками.

## 2. Что в Git, а что нет

В репозитории — **только `/local`** (наш код: шаблоны, компоненты, php_interface, stock-sync),
плюс `WORKFLOW.md` и `start-latitudo-ru.cmd`. Всё остальное вне Git и у каждой машины своё:

| Вне Git | Почему |
|---|---|
| `bitrix/` | ядро Битрикса, ~3 ГБ, тянется с прода |
| `upload/`, `images/`, `files/` | контент и медиа |
| `.htaccess` | на локали правится под окружение (см. раздел 6) |
| `bitrix/.settings.php`, `bitrix/php_interface/dbconn.php` | реквизиты локальной базы |
| `.osp/project.ini`, `.osp/apache/httpd-standalone.conf` | настройки OSPanel этой машины |
| `figma.token`, `.claude/settings.local.json` | личные ключи и настройки |
| `local/php_interface/dbconn.php`, `local/**/*.log` | служебное |

Конфликтов между машинами из-за этого не возникает — так задумано.

`start-latitudo-ru.cmd` машинонезависим: корень OSPanel он вычисляет от папки проекта,
поэтому `C:\OSPanel` и `D:\OSPanel` одинаково подходят. Проверяет он не порты, а ответ
самого сайта, так что чужие адреса модулей ему не мешают.

## 3. Что устаревает само — база

Контент правится в админке **прода**, а локальная база — это снимок на момент развёртывания.
После `git pull` код будет свежий, а тексты и фото — старые.

Снимок этой машины: **6 августа 2026**.

### Перелить дамп заново (5 минут)

```bash
# 1. Снять дамп на сервере (пишет ~/lc-transfer/db.sql.gz, ~55 МБ)
ssh regru-latitudo-ru "~/lc-transfer/dump.sh"

# 2. Скачать
scp regru-latitudo-ru:~/lc-transfer/db.sql.gz .

# 3. Залить локально (MySQL-5.7 на 127.0.1.26, база latitudo_lat)
"<OSPanel>/modules/MySQL-5.7/bin/mysql.exe" -h 127.0.1.26 -u root \
  -e "DROP DATABASE IF EXISTS latitudo_lat; CREATE DATABASE latitudo_lat CHARACTER SET utf8 COLLATE utf8_unicode_ci;"
gzip -dc db.sql.gz | "<OSPanel>/modules/MySQL-5.7/bin/mysql.exe" -h 127.0.1.26 -u root \
  --max_allowed_packet=512M --default-character-set=utf8 latitudo_lat

# 4. Вернуть домен локали и почистить кэш
"<OSPanel>/modules/MySQL-5.7/bin/mysql.exe" -h 127.0.1.26 -u root -e "
  UPDATE latitudo_lat.b_lang SET SERVER_NAME='latitudo-newdesign.loc' WHERE LID='s1';
  DELETE FROM latitudo_lat.b_lang_domain;
  INSERT INTO latitudo_lat.b_lang_domain (LID, DOMAIN) VALUES ('s1','latitudo-newdesign.loc');"
rm -rf bitrix/cache bitrix/managed_cache bitrix/stack_cache bitrix/html_pages
```

Требования: `max_allowed_packet` в секции `[mysqld]` конфига MySQL — **512M**
(на дефолтных 4 МБ импорт рвётся на середине).

## 4. Что сказать Клоду на другой машине

> Работаем над latitudo.ru, локалка на этой машине уже развёрнута. Читай WORKFLOW.md.
> Сделай git pull, проверь, что сайт поднимается, и покажи, насколько локальная база
> отстала от прода.

Если база устарела — он переливает дамп по разделу 3.

**Если движка и базы там нет:** `git pull` даст только код, и сайт не заведётся — Битриксу
нужен `bitrix/`. Тогда:

> Разрешаю установить SSH-ключ на прод самостоятельно, дотяни движок, медиа и базу с сервера.

Порядок развёртывания с нуля — раздел 5.

## 5. Развернуть локалку с нуля

1. `git clone` репозитория в `<OSPanel>/home/latitudo-newdesign.loc`.
2. Доступ к проду: ключ `~/.ssh/regru_latitudo_ru`, алиас `regru-latitudo-ru` в `~/.ssh/config`
   (`vip125.hosting.reg.ru`, пользователь `u0916578`).
3. Дотянуть с прода `bitrix/`, корневые php-файлы, `images/`, `files/`, `sit_files/`.
   **Не тянуть `upload/`** — 12 ГБ, вместо этого правило из раздела 6.
   **Качать максимум в 1–2 потока:** восемь параллельных потоков кладут боевой сайт
   (проверено 6 августа: доля 5xx у живых посетителей выросла с ~50/час до 3342/час).
4. Залить базу — раздел 3.
5. Прописать в `bitrix/.settings.php` и `bitrix/php_interface/dbconn.php` локальную базу:
   `127.0.1.26:3306`, `latitudo_lat`, `root`, пустой пароль.
6. Правки `.htaccess` — раздел 6.
7. В hosts: `127.0.1.12  latitudo-newdesign.loc` (адрес свободен и совпадает с конфигом Apache).
8. Синхронизировать время файлов с продом — раздел 7, иначе поедут стили.
9. Запуск — `start-latitudo-ru.cmd`.

Окружение локали: Apache на `127.0.1.12:80` (свой конфиг `.osp/apache/httpd-standalone.conf`),
PHP-8.2 FastCGI на `127.0.1.35:9001`, MySQL-**5.7** на `127.0.1.26` (как на проде; на 8.0 не проверялось).
У latitudo-pro.local свои адреса (`127.0.1.11` и порт `9000`) — сайты не конфликтуют.

## 6. Обязательные правки .htaccess на локали

Оригинал прода сохраняется рядом как `.htaccess.prod`. Нужны три вещи:

1. **Закомментировать голые `php_flag` / `php_value`.** PHP работает через FastCGI, а не mod_php,
   и Apache на этих директивах отдаёт 500 на весь сайт.
2. **Закомментировать блок «убираем CAPS»** (перебор букв `A→a` с флагом `[N]`). Локально он
   зацикливается, и любой файл с заглавной буквой в имени отдаёт 500 — в том числе JS шаблона,
   из-за чего «едет» вёрстка.
3. **Обойти принудительный редирект на https** для локального домена и добавить отдачу
   отсутствующих картинок с прода:

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^upload/(.*)$ https://latitudo.ru/upload/$1 [R=302,L]
```

Условие `!-f` означает: как только файл появится локально, правило перестанет срабатывать.

## 7. Грабли: «поехавшие» стили после git clone

Битрикс подключает `X.min.css` вместо `X.css`, если время у `.min` **не старше** исходника.
После `git clone` у всех файлов одинаковое время (момент checkout), поэтому побеждает `.min` —
а в репозитории он часто протухший. Итог: вместо `styles.css` (48 КБ) подключается
`styles.min.css` (12 КБ), и вёрстка разъезжается.

Лечение — перенести с прода настоящее время файлов:

```bash
ssh regru-latitudo-ru "cd ~/www/latitudo.ru/local && find . -type f \( -name '*.css' -o -name '*.js' \) -printf '%T@ %p\n' | sed 's/\.[0-9]* / /'" > mtimes.txt
cd local && while read -r ts p; do [ -f "${p#./}" ] && touch -d "@$ts" "${p#./}"; done < mtimes.txt
rm -rf ../bitrix/cache ../bitrix/managed_cache ../bitrix/stack_cache ../bitrix/html_pages
```

Проверка, что помогло: состав агрегата `/bitrix/cache/css/s1/aspro_next/template_*.css`
локально и на проде должен совпадать (сравнить по маркерам `/* Start:/путь */`).

Там же вторые грабли: после клонирования часть файлов может остаться с **CRLF**, тогда как
на проде везде LF. Git этого не показывает, пока не изменится время файла. Лечится
`git checkout -- .` (и повторным переносом времени после него).

## 8. Шаблоны сайта

По умолчанию работает **`aspro_next`**. Редизайн **`aspro_next_newdesign`** включается только
по `?newdesign=Y` — это ставит куку `NEWDESIGN` на 30 дней (условие в `b_site_template`).
То есть правки редизайна видно по адресу `http://latitudo-newdesign.loc/?newdesign=Y`.

## 9. Макет в Figma — источник истины по вёрстке

**Любая вёрстка нового дизайна делается по этому макету, а не «на глаз» и не по проду:**

https://www.figma.com/design/FDbZc7Ud6IURh8OZqEo97q/ЛАТИТУДО-FINAL-_-2026?node-id=20461-230932

- Файл: `FDbZc7Ud6IURh8OZqEo97q` — «ЛАТИТУДО FINAL / 2026».
- Страница `20461:230932` — **«Чистовик»**, ~170 фреймов: десктоп 1440 и мобилка 360
  для всех страниц плюс модалки. Работаем только с ней.
- В URL node-id пишется через дефис (`20461-230932`), в API — через двоеточие (`20461:230932`).

Ссылку нельзя открыть обычным запросом страницы — Figma отдаёт пустую SPA-оболочку.
Содержимое читается через REST API, токен (`figd_…`) лежит в `figma.token` в корне проекта
(файл вне Git, у каждой машины свой; аккаунт `latitudo.belgorod@gmail.com`):

```powershell
$t = (Get-Content "figma.token" -Raw).Trim()
# структура — обязательно ограничивать depth, иначе ответ огромный
Invoke-RestMethod "https://api.figma.com/v1/files/FDbZc7Ud6IURh8OZqEo97q/nodes?ids=20461:230932&depth=2" -Headers @{ "X-Figma-Token" = $t }
# рендер фрейма в PNG — вернёт временную ссылку, её нужно скачать отдельно
Invoke-RestMethod "https://api.figma.com/v1/images/FDbZc7Ud6IURh8OZqEo97q?ids=<node>&format=png&scale=1" -Headers @{ "X-Figma-Token" = $t }
```

Порядок работы над экраном: найти нужный фрейм в «Чистовике» → взять из API размеры,
отступы, цвета, шрифты и радиусы → верстать по ним. Расхождение с макетом — баг,
даже если «выглядит нормально». Если в макете чего-то нет — спросить, а не додумывать.

MCP-коннектора к Figma нет и не требуется.
