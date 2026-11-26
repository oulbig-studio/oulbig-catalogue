#!/usr/bin/env python3
"""Affiches de la série L'Effet d'Amour pour les réseaux sociaux.

Usage : python3 affiches.py VISUEL.png NOM_SORTIE [DOSSIER_POLICES]
Produit, pour chaque langue (fr, darija, en), 4 formats :
  story 1080x1920 (TikTok, Reels, Stories) · post 1080x1350 (Instagram) · carre 1080x1080 · youtube 1920x1080
Les titres sont posés en vraie typographie (pas générés par l'IA), donc sans fautes.
"""
import os, sys
from PIL import Image, ImageDraw, ImageFilter, ImageFont

FONTS = sys.argv[3] if len(sys.argv) > 3 else os.path.join(os.path.dirname(os.path.abspath(__file__)), 'polices')
F = {
    'titre': os.path.join(FONTS, 'playfair-display-latin-700-normal.woff'),
    'italique': os.path.join(FONTS, 'playfair-display-latin-400-italic.woff'),
    'texte': os.path.join(FONTS, 'cormorant-garamond-latin-600-normal.woff'),
    'arabe': os.path.join(FONTS, 'amiri-arabic-700-normal.woff'),
    'arabe_r': os.path.join(FONTS, 'amiri-arabic-400-normal.woff'),
}
TXT = {
    'fr': dict(titre="L'Effet d'Amour", accroche="On revient toujours pour quelqu'un.",
               credit="D'après le roman d'Ash Oulbig", bas="UNE SÉRIE OULBIG STUDIOS · ÉPISODE 1 DISPONIBLE", rtl=False),
    'en': dict(titre="The Love Effect", accroche="We always come back for someone.",
               credit="Based on the novel by Ash Oulbig", bas="AN OULBIG STUDIOS SERIES · EPISODE 1 OUT NOW", rtl=False),
    'darija': dict(titre="تأثير الحب", accroche="ديما كنرجعو على قبل شي واحد",
                   credit_ar="على رواية", credit="Ash Oulbig", bas_ar="الحلقة الأولى متوفرة", bas="OULBIG STUDIOS", rtl=True),
}
FORMATS = {'story': (1080, 1920), 'post': (1080, 1350), 'carre': (1080, 1080), 'youtube': (1920, 1080)}
GOLD = (240, 200, 140)


def cover(img, w, h):
    r = max(w / img.width, h / img.height)
    im = img.resize((round(img.width * r), round(img.height * r)), Image.LANCZOS)
    x, y = (im.width - w) // 2, (im.height - h) // 2
    return im.crop((x, y, x + w, y + h))


def fond(img, w, h):
    if w > h:  # format large : image nette au centre, même image floutée derrière
        bg = cover(img, w, h).filter(ImageFilter.GaussianBlur(40))
        r = h / img.height
        fg = img.resize((round(img.width * r), h), Image.LANCZOS)
        bg.paste(fg, ((w - fg.width) // 2, 0))
        return bg
    return cover(img, w, h)


def degrade(im, haut=0.12, bas=0.50):
    w, h = im.size
    ov = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(ov)
    for y in range(h):
        a = 0
        if y < h * haut:
            a = int(190 * (1 - y / (h * haut)))
        elif y > h * (1 - bas):
            a = int(220 * ((y - h * (1 - bas)) / (h * bas)))
        d.line([(0, y), (w, y)], fill=(10, 6, 4, a))
    return Image.alpha_composite(im.convert('RGBA'), ov)


def texte(d, xy_centre, s, police, taille, couleur, rtl=False, ombre=True, largeur_max=None):
    font = ImageFont.truetype(police, taille)
    kw = dict(direction='rtl', language='ar') if rtl else {}
    while largeur_max and d.textlength(s, font=font, **kw) > largeur_max and taille > 20:
        taille -= 4
        font = ImageFont.truetype(police, taille)
    x, y = xy_centre
    if ombre:
        d.text((x + 3, y + 3), s, font=font, fill=(0, 0, 0, 160), anchor='mm', **kw)
    d.text((x, y), s, font=font, fill=couleur, anchor='mm', **kw)


def affiche(img, lang, fmt):
    w, h = FORMATS[fmt]
    t = TXT[lang]
    im = degrade(fond(img, w, h))
    d = ImageDraw.Draw(im)
    base = min(w, h)
    ar = lang == 'darija'
    pos = lambda k: h - base * k
    texte(d, (w / 2, pos(0.36)), t['titre'], F['arabe'] if ar else F['titre'], int(base * (0.15 if ar else 0.12)),
          (255, 245, 230), rtl=ar, largeur_max=w * 0.9)
    texte(d, (w / 2, pos(0.255)), t['accroche'], F['arabe_r'] if ar else F['italique'], int(base * (0.05 if ar else 0.045)),
          GOLD, rtl=ar, largeur_max=w * 0.88)
    if ar:
        texte(d, (w / 2, pos(0.175)), t['credit_ar'], F['arabe_r'], int(base * 0.04), (255, 245, 230), rtl=True)
        texte(d, (w / 2, pos(0.125)), t['credit'], F['texte'], int(base * 0.042), (255, 245, 230))
        texte(d, (w / 2, pos(0.075)), t['bas_ar'], F['arabe_r'], int(base * 0.032), GOLD, rtl=True)
        texte(d, (w / 2, pos(0.035)), t['bas'], F['texte'], int(base * 0.026), GOLD)
    else:
        texte(d, (w / 2, pos(0.15)), t['credit'], F['texte'], int(base * 0.042), (255, 245, 230), largeur_max=w * 0.9)
        texte(d, (w / 2, pos(0.08)), t['bas'], F['texte'], int(base * 0.028), GOLD, largeur_max=w * 0.92)
    return im.convert('RGB')


def main():
    src, nom = sys.argv[1], sys.argv[2]
    img = Image.open(src).convert('RGB')
    out = os.path.dirname(os.path.abspath(nom)) or '.'
    for lang in TXT:
        for fmt in FORMATS:
            p = f'{nom}_{lang}_{fmt}.jpg'
            affiche(img, lang, fmt).save(p, quality=92)
            print(p)


if __name__ == '__main__':
    main()
