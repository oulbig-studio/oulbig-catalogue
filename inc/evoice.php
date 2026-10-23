<?php
/**
 * ABS Records — E-Voice : remplace le catalogue de démonstration du thème one-page
 * (tableaux BOOKS / EBOOKS de assets/main.js) par les vrais titres :
 * - Audiolivres : les épisodes audio du podcast L'Étincelle (catalogue OULBIG, mis à jour automatiquement) ;
 * - Ebooks : « Introduction : 37 étapes + 1 », « L'Effet d'Amour » et les guides L'Étincelle à venir ;
 * - « À la une » : Introduction : 37 étapes + 1, avec un vrai extrait audio.
 * Ne modifie aucun fichier du thème : le script s'exécute juste après assets/main.js.
 */
if (!defined('ABSPATH')) exit;

function oulbig_evoice_books() {
    $wa = 'https://wa.me/212661692067?text=';
    $buy = function ($t) use ($wa) { return $wa . rawurlencode('Bonjour, je souhaite commander l\'ebook « ' . $t . ' ».'); };
    $all = ['FR', 'EN', 'ES', 'AR', 'DA'];
    $soon = ['fr' => 'Bientôt', 'en' => 'Soon', 'ar' => 'قريباً'];
    $books = [
        ['ci' => 2, 'fr' => 'Introduction : 37 étapes + 1', 'en' => 'Introduction: 37 Steps + 1', 'ar' => 'مقدمة: 37 خطوة + 1',
         'sub' => ['fr' => 'OULBIG · Saga L\'Étincelle, tome 1', 'en' => 'OULBIG · The Spark saga, book 1', 'ar' => 'OULBIG · سلسلة لمحة، الجزء 1'],
         'dur' => 'PDF · EPUB', 'price' => '100 MAD', 'langs' => $all, 'buy' => $buy('Introduction : 37 étapes + 1'), 'c' => ['#6e4a1e', '#e6a94e']],
        ['ci' => 11, 'fr' => 'L\'Effet d\'Amour — Ce que Béa m\'a appris', 'en' => 'The Effect of Love — What Béa Taught Me', 'ar' => 'أثر الحب — ما علّمتني إياه بيا',
         'sub' => ['fr' => 'Roman · Ash Oulbig', 'en' => 'Novel · Ash Oulbig', 'ar' => 'رواية · Ash Oulbig'],
         'dur' => 'PDF · EPUB', 'price' => '70 MAD', 'langs' => $all, 'buy' => $buy('L\'Effet d\'Amour'), 'c' => ['#6e2e3a', '#c84f64']],
        ['ci' => 2, 'fr' => 'لمحة — دليل التغيير', 'en' => 'لمحة — دليل التغيير', 'ar' => 'لمحة — دليل التغيير',
         'sub' => ['fr' => 'Guide L\'Étincelle · OULBIG', 'en' => 'The Spark guide · OULBIG', 'ar' => 'دليل لمحة · OULBIG'],
         'dur' => 'PDF', 'price' => $soon, 'langs' => ['AR'], 'buy' => '', 'c' => ['#2e5a52', '#4fb39a']],
        ['ci' => 10, 'fr' => 'Bien vivre — 5 fondations', 'en' => 'Living Well — 5 Foundations', 'ar' => 'العيش الجيد — 5 أسس',
         'sub' => ['fr' => 'Guide L\'Étincelle · OULBIG', 'en' => 'The Spark guide · OULBIG', 'ar' => 'دليل لمحة · OULBIG'],
         'dur' => 'PDF', 'price' => $soon, 'langs' => ['FR'], 'buy' => '', 'c' => ['#2e6e5a', '#4fc8a8']],
        ['ci' => 0, 'fr' => 'Les 6 sources d\'influence', 'en' => 'The 6 Sources of Influence', 'ar' => 'مصادر التأثير الستة',
         'sub' => ['fr' => 'Guide L\'Étincelle · OULBIG', 'en' => 'The Spark guide · OULBIG', 'ar' => 'دليل لمحة · OULBIG'],
         'dur' => 'PDF', 'price' => $soon, 'langs' => ['FR'], 'buy' => '', 'c' => ['#7b3f2e', '#c8623c']],
        ['ci' => 0, 'fr' => 'Devenir intéressant — 10 clés', 'en' => 'Becoming Interesting — 10 Keys', 'ar' => 'كيف تصبح شخصاً مثيراً للاهتمام — 10 مفاتيح',
         'sub' => ['fr' => 'Guide L\'Étincelle · OULBIG', 'en' => 'The Spark guide · OULBIG', 'ar' => 'دليل لمحة · OULBIG'],
         'dur' => 'PDF', 'price' => $soon, 'langs' => ['FR'], 'buy' => '', 'c' => ['#5a3a6e', '#a061b8']],
    ];
    return apply_filters('oulbig_evoice_books', $books);
}

function oulbig_evoice_data() {
    $map  = ['fr' => 'FR', 'en' => 'EN', 'es' => 'ES', 'ar' => 'AR', 'ma' => 'DA'];
    $eps  = [];
    $feat = null;
    foreach (oulbig_sync_pick('podcast', 500) as $n => $i) {
        $d = (int) ($i['duration'] ?? 0);
        $e = [
            'title' => $i['title'], 'lang' => $map[$i['lang']] ?? strtoupper($i['lang']), 'l' => $i['lang'],
            'yt' => $i['youtube']['id'], 'thumb' => $i['thumb'] ?: 'https://i.ytimg.com/vi/' . $i['youtube']['id'] . '/hqdefault.jpg',
            'dur' => $d ? floor($d / 60) . ':' . str_pad($d % 60, 2, '0', STR_PAD_LEFT) : '', 'artist' => $i['artist'],
            'etincelle' => (bool) preg_match('/tincelle|spark|chispa|لمحة/iu', $i['title']),
        ];
        if (!$feat && $e['etincelle'] && $i['lang'] === 'fr') $feat = $e;
        // Grille : les 10 épisodes les plus récents + les épisodes L'Étincelle (jusqu'à 16 au total).
        if ($n < 10 || $e['etincelle']) $eps[] = $e;
    }
    if (!$feat && $eps) $feat = $eps[0];
    return ['episodes' => array_slice($eps, 0, 16), 'featured' => $feat, 'books' => oulbig_evoice_books()];
}

add_action('wp_enqueue_scripts', function () {
    if (!oulbig_sync_showcase_enabled() || !wp_script_is('absev-main', 'enqueued')) return;
    $data = oulbig_evoice_data();
    if (!$data['episodes'] && !$data['books']) return;
    wp_add_inline_script('absev-main', 'window.OULBIG_EVOICE=' . wp_json_encode($data) . ";\n" . oulbig_evoice_js(), 'after');
    wp_add_inline_style('absev-main', oulbig_evoice_css());
}, 30);

function oulbig_evoice_css() {
    return <<<'CSS'
.cover.cover--img{aspect-ratio:16/10;background-size:cover;background-position:center}
.cover--img .ctitle{display:none}
.card-body h4{overflow-wrap:anywhere}
.card .ep-lang{font-size:.6rem;font-weight:800;letter-spacing:.05em;border:1px solid var(--line);padding:2px 6px;border-radius:5px;color:var(--gold-soft)}
.add.add--soon{opacity:.55;cursor:default}
.add.add--soon:hover{background:var(--surface-2);color:var(--cream);border-color:var(--line)}
.obx-modal{position:fixed;inset:0;z-index:120;background:rgba(10,8,6,.86);backdrop-filter:blur(6px);display:grid;place-items:center;padding:20px}
.obx-modal[hidden]{display:none}
.obx-modal__box{width:min(960px,100%);background:var(--surface);border:1px solid var(--line);border-radius:20px;overflow:hidden;box-shadow:var(--shadow)}
.obx-modal__video{position:relative;aspect-ratio:16/9;background:#000}
.obx-modal__video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.obx-modal__bar{display:flex;align-items:center;gap:14px;padding:14px 18px}
.obx-modal__bar b{flex:1;font-size:.95rem;overflow-wrap:anywhere}
.obx-modal__close{background:var(--gold);color:var(--ink);border:0;border-radius:999px;width:36px;height:36px;font-size:1.1rem;font-weight:800;cursor:pointer}
CSS;
}

function oulbig_evoice_js() {
    return <<<'JS'
(function(D){
  if(!D||typeof BOOKS==='undefined'||typeof EBOOKS==='undefined')return;
  function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
  function pick(v){return (v&&typeof v==='object')?(v[LANG]||v.fr):v;}
  var L={fr:{listen:'▶ Écouter',order:'Commander',soon:'Bientôt',ep:'Podcast L\'Étincelle',pod:'Podcast OULBIG'},
         en:{listen:'▶ Listen',order:'Order',soon:'Soon',ep:'The Spark podcast',pod:'OULBIG podcast'},
         ar:{listen:'▶ استمع',order:'اطلب',soon:'قريباً',ep:'بودكاست لمحة',pod:'بودكاست OULBIG'}};

  /* Audiolivres → épisodes audio réels */
  BOOKS.length=0;
  (D.episodes||[]).forEach(function(e){BOOKS.push({ci:2,fr:e.title,en:e.title,ar:e.title,dur:e.dur,price:'',rate:5,langs:[e.lang],yt:e.yt,thumb:e.thumb,ep:e});});
  /* Ebooks → livres réels */
  EBOOKS.length=0;
  (D.books||[]).forEach(function(b){EBOOKS.push(b);});

  /* À la une */
  ['fr','en','ar'].forEach(function(l){
    var t={fr:['Introduction : 37 étapes + 1',"OULBIG · Saga L'Étincelle, tome 1",'Écouter un extrait —','Nouveauté'],
           en:['Introduction: 37 Steps + 1','OULBIG · The Spark saga, book 1','Listen to a sample —','New'],
           ar:['مقدمة: 37 خطوة + 1','OULBIG · سلسلة لمحة، الجزء 1','استمع إلى مقتطف —','جديد']}[l];
    I18N[l].feat_title=t[0];I18N[l].feat_auth=t[1];I18N[l].feat_now=t[2];I18N[l].feat_cat=t[3];
  });
  var F=D.featured;
  if(F){
    var nb=document.querySelector('#player .now b'); if(nb){nb.textContent=F.title;nb.setAttribute('dir','auto');}
    var tm=document.querySelector('#player .time'); if(tm&&F.dur){tm.innerHTML='<span id="cur">00:00</span> / '+esc(F.dur);}
    var pb=document.getElementById('playBtn'); if(pb){pb.onclick=function(){openYT(F.yt,F.title);};}
  }

  /* Rendu des cartes */
  card=function(item,isEbook){
    var title=pick(item[LANG]?item[LANG]:item.fr), cat=CATS[item.ci]||CATS[0], T2=L[LANG]||L.fr;
    var flags=(item.langs||[]).map(function(x){return '<span>'+esc(x)+'</span>';}).join('');
    var hover='<div class="play-hover"><span class="pcirc"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span></div>';
    var cov, body, act;
    if(item.yt){
      var tag=item.ep&&item.ep.etincelle?T2.ep:T2.pod;
      cov='<div class="cover cover--img" style="background-image:linear-gradient(180deg,rgba(20,17,13,.1),rgba(20,17,13,.55)),url(\''+esc(item.thumb)+'\')"><span class="ctag">'+esc(tag)+'</span><span class="type-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="#fff"><path d="M8 5v14l11-7z"/></svg></span>'+hover+'</div>';
      act='data-yt="'+esc(item.yt)+'" data-title="'+esc(title)+'"';
      body='<h4 dir="auto">'+esc(title)+'</h4><div class="nar">'+esc(item.ep.artist)+' · '+esc(cat[LANG]||cat.fr)+'</div>'
        +'<div class="meta"><span class="ep-lang">'+esc(item.langs[0])+'</span><span class="dur">'+esc(item.dur)+'</span></div>'
        +'<button class="add" style="margin-top:10px">'+T2.listen+'</button>';
    }else{
      var c=item.c||cat.c, price=pick(item.price);
      cov='<div class="cover" style="background:linear-gradient(155deg,'+c[0]+','+c[1]+')"><span class="ctag">'+esc(cat.n+' · '+(cat[LANG]||cat.fr))+'</span><span class="ctitle" dir="auto">'+esc(title)+'</span><span class="type-badge"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span></div>';
      act=item.buy?'data-buy="'+esc(item.buy)+'"':'';
      body='<h4 dir="auto">'+esc(title)+'</h4><div class="nar">'+esc(pick(item.sub)||'')+'</div>'
        +'<div class="meta"><span class="dur">'+esc(item.dur)+'</span></div>'
        +'<div class="price-row"><span class="price">'+esc(price)+'</span><div class="langflags">'+flags+'</div></div>'
        +(item.buy?'<button class="add" style="margin-top:10px">'+T2.order+'</button>':'<button class="add add--soon" style="margin-top:10px" disabled>'+T2.soon+'</button>');
    }
    return '<article class="card" '+act+'>'+cov+'<div class="card-body">'+body+'</div></article>';
  };

  /* Lecteur (fenêtre) */
  var M=document.createElement('div'); M.className='obx-modal'; M.hidden=true;
  M.innerHTML='<div class="obx-modal__box" role="dialog" aria-modal="true"><div class="obx-modal__video"></div><div class="obx-modal__bar"><b dir="auto"></b><button type="button" class="obx-modal__close" aria-label="Fermer">×</button></div></div>';
  document.body.appendChild(M);
  function openYT(id,t){M.querySelector('.obx-modal__video').innerHTML='<iframe src="https://www.youtube-nocookie.com/embed/'+encodeURIComponent(id)+'?autoplay=1&rel=0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="'+esc(t)+'"></iframe>';M.querySelector('b').textContent=t;M.hidden=false;}
  function closeYT(){M.hidden=true;M.querySelector('.obx-modal__video').innerHTML='';}
  M.addEventListener('click',function(e){if(e.target===M||e.target.closest('.obx-modal__close'))closeYT();});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!M.hidden)closeYT();});
  document.addEventListener('click',function(e){
    var c=e.target.closest('#bookGrid .card,#ebookGrid .card'); if(!c)return;
    if(c.dataset.yt){openYT(c.dataset.yt,c.dataset.title);}
    else if(c.dataset.buy){window.open(c.dataset.buy,'_blank','noopener');}
  });

  setLang(LANG);
})(window.OULBIG_EVOICE);
JS;
}
