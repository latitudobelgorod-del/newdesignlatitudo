from PIL import Image, ImageDraw, ImageFont
im = Image.open('cm_5t9tqx03gjd5y71zsxbywpaszikgwbro.jpg').convert('RGB')
d = ImageDraw.Draw(im)
d.rectangle([0, 60, 392, 455], fill=(244, 244, 244))
def font(size, style):
    f = ImageFont.truetype('C:/Windows/Fonts/bahnschrift.ttf', size)
    f.set_variation_by_name(style)
    return f
print(ImageFont.truetype('C:/Windows/Fonts/bahnschrift.ttf', 10).get_variation_names())
# большая цифра с вертикальным градиентом, как на исходнике
big = font(112, b'Bold Condensed')
txt = 'до -50%'
mask = Image.new('L', im.size, 0)
ImageDraw.Draw(mask).text((40, 78), txt, font=big, fill=255)
grad = Image.new('RGB', im.size)
gd = ImageDraw.Draw(grad)
for y in range(im.size[1]):
    t = min(max((y - 90) / 110, 0), 1)
    gd.line([(0, y), (im.size[0], y)], fill=(int(250 - 18 * t), int(160 - 70 * t), int(90 - 60 * t)))
im.paste(grad, (0, 0), mask)
f2 = font(40, b'SemiBold Condensed')
y = 212
for line in ['Террасная доска,', 'фасадные и стеновые', 'панели CM Decking']:
    d.text((40, y), line, font=f2, fill=(24, 24, 24))
    y += 48
f3 = font(28, b'SemiLight Condensed')
pill = 'до 30 ноября'
w = d.textlength(pill, font=f3)
d.rounded_rectangle([40, 375, 40 + w + 40, 421], radius=23, fill=(236, 110, 40))
d.text((60, 381), pill, font=f3, fill=(255, 255, 255))
im.save('cm_promo_oct_nov_2026.jpg', quality=90)
# логотип CM Decking вместо LATITUDO
im = Image.open('cm_promo_oct_nov_2026.jpg').convert('RGB')
ImageDraw.Draw(im).rectangle([0, 470, 400, 567], fill=(244, 244, 244))
logo = Image.open('cm_logo_det.jpg').convert('RGB')
logo = logo.crop((70, 35, 1540, 475))
w = 230; logo = logo.resize((w, round(logo.height * w / logo.width)), Image.LANCZOS)
from PIL import ImageChops
bg = Image.new('RGB', logo.size, (244, 244, 244))
logo = ImageChops.multiply(logo, bg.point(lambda v: 255)).point(lambda v: v)
mask = ImageChops.invert(logo.convert('L')).point(lambda v: min(255, v * 3))
im.paste(logo, (40, 478), mask)
im.save('cm_promo_oct_nov_2026.jpg', quality=90)
