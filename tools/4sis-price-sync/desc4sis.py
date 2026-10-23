# Полные описания новых товаров 4SIS со страниц 4sis.ru (в фиде описание обрезано до 500 знаков). 23.10.2026
import csv,io,re,glob,json,time,html,urllib.request,gzip,os
def n(s): s=s.lower().replace("ё","е"); return " ".join(re.sub(r"[^\w]+"," ",s).split())
H=os.path.expanduser("~")
site={}
for f in [H+"/nd_4sis/4sis.tsv"]+glob.glob(H+"/nd_4sis/out_part_*.tsv"):
  for l in io.open(f,encoding="utf-8"):
    c=l.rstrip("\n").split("\t")
    if len(c)>2 and c[2]: site.setdefault(n(c[2]),c[0])
rows=[r for r in csv.reader(io.open("feed_not_on_site.csv",encoding="utf-8-sig"),delimiter=";")][1:]
out=json.load(open("desc.json")) if os.path.exists("desc.json") else {}
for r in rows:
  u=site.get(n(r[4]))
  if r[0]!="нет у нас" or not u or r[1] in out: continue
  try:
    req=urllib.request.Request(u,headers={"User-Agent":"Mozilla/5.0","Accept-Encoding":"gzip"})
    with urllib.request.urlopen(req,timeout=60) as x:
      b=x.read(); b=gzip.decompress(b) if x.headers.get("Content-Encoding")=="gzip" else b
    s=b.decode(x.headers.get_content_charset() or "utf-8","replace")
    m=re.search(r"<div class=\"content\" itemprop=\"description\">(.*?)</div>\s*</div>\s*<div class=\"tab-pane",s,re.S)
    if m:
      t=re.sub(r"<br\s*/?>[ \t]*\r?\n?","\n",m.group(1));  # <br /> и перевод строки за ним — один перенос t=re.sub(r"</p>\s*","\n\n",t); t=html.unescape(re.sub(r"<[^>]+>","",t))
      t="\n".join(l.strip() for l in t.replace("\r","").split("\n")); t=re.sub(r"\n{3,}","\n\n",t).strip()
      out[r[1]]={"url":u,"text":t}
  except Exception as e: print("ERR",r[1],e)
  time.sleep(1)
json.dump(out,open("desc.json","w"),ensure_ascii=False,indent=1)
print("описаний",len(out))
