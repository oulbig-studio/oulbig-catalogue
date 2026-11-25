#!/usr/bin/env python3
"""Montage de l'épisode 1 de L'Effet d'Amour.

Usage : python3 montage_ep01.py DOSSIER_DES_CLIPS SORTIE.mp4

Le dossier des clips contient les vidéos générées, nommées comme dans SEGMENTS ci-dessous.
Un plan sans vidéo devient un carton noir avec son numéro, pour garder le rythme.
Pour remplacer un plan : déposez la nouvelle vidéo dans le dossier, mettez son nom dans SEGMENTS, relancez.
"""
import csv, os, subprocess, sys, tempfile

HERE = os.path.dirname(os.path.abspath(__file__))
VO_DIR = os.environ.get('VO_DIR', os.path.join(HERE, '..', 'voix-off', 'ep01'))
SCRIPT = os.environ.get('SCRIPT', 'script.tsv')
SUB_COL = os.environ.get('SUB_COL', 'texte')
VO_TRACK = os.environ.get('VO_TRACK', 'EP01_VOIX-OFF_COMPLETE.mp3')
SEG_OVERRIDE = os.environ.get('SEG_DURATIONS')
FONT = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
W, H, FPS = 1080, 1920, 24
AMBIENT = 0.20      # volume du son des vidéos
FADE = 0.25         # fondu au noir entre les plans (s)

# plan, durée (s), vidéo (None = carton), début dans la vidéo (s), vitesse (1 = normale), texte du carton
SEGMENTS = [
    (1, 3.0, None, 0, 1, 'PLAN 1 — supermarché (à générer)'),
    (2, 2.0, 'TITLE', 0, 1, '4 jours plus tôt'),
    (3, 6.6, 'plan01.mp4', 0.0, 1, ''),
    (4, 4.0, None, 0, 1, 'PLAN 4 — le car, le vieil homme'),
    (5, 5.0, None, 0, 1, 'PLAN 5 — la gare routière'),
    (6, 4.5, None, 0, 1, 'PLAN 6 — le taxi'),
    (7, 5.0, 'plan02.mp4', 0.0, 1, ''),
    (8, 4.0, None, 0, 1, "PLAN 8 — l'âne"),
    (9, 4.0, None, 0, 1, 'PLAN 9 — la boulangerie'),
    (10, 4.8, None, 0, 1, 'PLAN 10 — le mur bas'),
    (11, 7.1, 'plan03.mp4', 0.4, 1, ''),
    (12, 10.0, 'plan04.mp4', 0.0, 0.79, ''),   # plans 12 + 13 : le calligraphe, légèrement ralenti
    (14, 8.1, None, 0, 1, 'PLAN 14 — le calligraphe parle'),
    (15, 4.0, None, 0, 1, 'PLAN 15 — les yeux de H'),
    (16, 6.0, 'plan05.mp4', 1.9, 1, ''),
    (99, 2.0, 'TITLE', 0, 1, 'À suivre…'),
]


TITLE_TR = {'4 jours plus tôt': '4 days earlier', 'À suivre…': 'To be continued…'} if os.environ.get('TITLES') == 'en' else {}


def run(cmd):
    subprocess.run(cmd, check=True)


def esc(t):
    return t.replace('\\', '\\\\').replace(':', '\\:').replace("'", "’").replace('%', '\\%')


def segment(i, seg, clips, tmp):
    plan, dur, src, start, speed, text = seg
    out = os.path.join(tmp, f'seg{i:02d}.mp4')
    fade = f'fade=t=in:st=0:d={FADE},fade=t=out:st={dur - FADE:.2f}:d={FADE}'
    path = os.path.join(clips, src) if src and src != 'TITLE' else None
    if path and os.path.exists(path):
        vf = (f'setpts=PTS/{speed},scale={W}:{H}:force_original_aspect_ratio=increase:flags=lanczos,'
              f'crop={W}:{H},fps={FPS},tpad=stop_mode=clone:stop_duration={dur},{fade}')
        af = f'atempo={speed},volume={AMBIENT},apad,afade=t=in:d={FADE},afade=t=out:st={dur - FADE:.2f}:d={FADE}' if speed >= 0.5 else f'volume={AMBIENT}'
        run(['ffmpeg', '-v', 'error', '-y', '-ss', str(start), '-i', path, '-t', f'{dur}',
             '-vf', vf, '-af', af, '-t', f'{dur}', '-r', str(FPS), '-c:v', 'libx264', '-preset', 'medium',
             '-crf', '18', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-ar', '44100', '-ac', '2', out])
    else:
        big = src == 'TITLE'
        size, color = (64, 'white') if big else (40, 'gray')
        label = esc(TITLE_TR.get(text, text) if text else f'PLAN {plan}')
        vf = (f"drawtext=fontfile={FONT}:text='{label}':fontcolor={color}:fontsize={size}:"
              f"x=(w-text_w)/2:y=(h-text_h)/2,{fade}")
        run(['ffmpeg', '-v', 'error', '-y', '-f', 'lavfi', '-i', f'color=c=black:s={W}x{H}:r={FPS}:d={dur}',
             '-f', 'lavfi', '-i', f'anullsrc=r=44100:cl=stereo', '-t', f'{dur}', '-vf', vf,
             '-c:v', 'libx264', '-crf', '18', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-ar', '44100', out])
    return out


def ass_time(t):
    h, m = int(t // 3600), int(t % 3600 // 60)
    return f'{h}:{m:02d}:{t % 60:05.2f}'


def subtitles(tmp):
    texts = {r['fichier']: r[SUB_COL] for r in csv.DictReader(open(os.path.join(VO_DIR, SCRIPT)), delimiter='\t')}
    rows = [r for r in csv.DictReader(open(os.path.join(VO_DIR, os.environ.get('MONTAGE_TSV', 'EP01_MONTAGE.tsv'))), delimiter='\t') if r['fichier audio']]
    lines = []
    for r in rows:
        f = r['fichier audio']
        start = float(r['audio démarre à (s)'])
        d = float(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration',
                                           '-of', 'csv=p=0', os.path.join(VO_DIR, f)]))
        lines.append((start, start + d + 0.3, texts[f]))
    path = os.path.join(tmp, 'subs.ass')
    with open(path, 'w', encoding='utf-8') as o:
        o.write('[Script Info]\nScriptType: v4.00+\nPlayResX: 1080\nPlayResY: 1920\nWrapStyle: 0\n\n'
                '[V4+ Styles]\nFormat: Name, Fontname, Fontsize, PrimaryColour, OutlineColour, BackColour, Bold, '
                'BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV\n'
                'Style: Default,DejaVu Sans,58,&H00FFFFFF,&H00000000,&H80000000,1,1,4,1,2,90,90,420\n\n'
                '[Events]\nFormat: Layer, Start, End, Style, Text\n')
        for s, e, t in lines:
            o.write(f'Dialogue: 0,{ass_time(s)},{ass_time(e)},Default,{t}\n')
    return path


def main():
    clips, out = sys.argv[1], sys.argv[2]
    with tempfile.TemporaryDirectory() as tmp:
        segments = SEGMENTS
        if SEG_OVERRIDE:
            d = dict((int(k), float(v)) for k, v in (x.split('=') for x in SEG_OVERRIDE.split(',')))
            segments = [(p, d.get(p, du), *rest) for p, du, *rest in SEGMENTS]
        segs = [segment(i, s, clips, tmp) for i, s in enumerate(segments)]
        lst = os.path.join(tmp, 'list.txt')
        open(lst, 'w').write(''.join(f"file '{s}'\n" for s in segs))
        body = os.path.join(tmp, 'body.mp4')
        run(['ffmpeg', '-v', 'error', '-y', '-f', 'concat', '-safe', '0', '-i', lst, '-c', 'copy', body])
        subs = subtitles(tmp)
        vo = os.path.join(VO_DIR, VO_TRACK)
        run(['ffmpeg', '-v', 'error', '-y', '-i', body, '-i', vo, '-filter_complex',
             f"[0:v]ass={subs}[v];[0:a][1:a]amix=inputs=2:duration=first:normalize=0,loudnorm=I=-14:TP=-1.5:LRA=11[a]",
             '-map', '[v]', '-map', '[a]', '-c:v', 'libx264', '-preset', 'medium', '-crf', '19', '-pix_fmt', 'yuv420p',
             '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart', out])
    print('OK', out)


if __name__ == '__main__':
    main()
