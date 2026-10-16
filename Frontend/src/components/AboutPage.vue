<template>
  <main class="about-page content-width" aria-labelledby="about-title">
    <a class="about-back" href="#/sakums">← Atpakaļ uz sākumu</a>
    <header class="about-intro">
      <span class="eyebrow">♡ Mūsu stāsts · Par mums</span>
      <h1 id="about-title">Mazs gabaliņš šokolādes.<br /><span class="pink-text">Vesela pasaule sajūtu.</span></h1>
      <p>Ir mirkļi, kuri neprasa daudz. Tikai mazliet laika sev, kādu tuvu cilvēku blakus un garšu, kas liek pasmaidīt. ShocoStore ir mūsu aicinājums šos mirkļus pamanīt un izbaudīt.</p>
    </header>

    <section class="about-heart" aria-labelledby="about-heart-title">
      <span class="about-kicker">ŠOKOLĀDE AR RAKSTURU. STĀSTS AR SIRDI.</span>
      <h2 id="about-heart-title">Skaistais sākas ar <span>mazajiem priekiem.</span></h2>
      <p>Mēs ticam, ka ikdienā ir vieta mazai burvībai. Tā dzīvo pirmajā saules starā, sirsnīgā sarunā un tajā klusajā mirklī, kad šokolāde lēni kūst uz mēles un pasaule uz brīdi kļūst maigāka.</p>
      <p>ShocoStore centrā ir vienkārša doma: dāvāt garšai vietu tavā stāstā. Kā saldai pauzei pēc garas dienas, kā mazam pārsteigumam cilvēkam, kuru mīli, vai kā apsveikumam pašam sev par soli, ko beidzot uzdrošinājies spert.</p>
      <p>Mums šokolāde nozīmē siltumu, ko var pasniegt bez gariem paskaidrojumiem. Reizēm viens gabaliņš pasaka “paldies”, “es domāju par tevi” vai “tu esi pelnījis kaut ko skaistu” daudz labāk nekā vesels teikums.</p>
    </section>

    <section class="about-values" aria-labelledby="about-values-title">
      <h2 id="about-values-title">Tas, kam mēs ticam.</h2>
      <div class="about-value-grid">
        <article><span class="about-number">01 / PRIEKS</span><h3>Ikdiena ir svētku vērta.</h3><p>Lai palutinātu sevi, nav jāgaida īpašs datums. Arī parasta otrdiena var kļūt par mazu svētku dienu, ja tajā atvēlam vietu kaut kam patīkamam.</p></article>
        <article><span class="about-number">02 / RAKSTURS</span><h3>Tavai garšai ir sava balss.</h3><p>Maiga, drosmīga, saulaina vai mazliet negaidīta — katra garša aicina atklāt ko jaunu. Izvēlies to, kas šodien saskan ar tevi, un ļauj sev rīt pārsteigties no jauna.</p></article>
        <article><span class="about-number">03 / TUVUMS</span><h3>Dalīts prieks paliek ilgāk.</h3><p>Skaistākie mirkļi bieži sākas ar pavisam vienkāršu žestu. Piedāvā gabaliņu, apsēdies blakus, uzklausi. Saldums beidzas, bet sajūta, ka esi kādam svarīgs, paliek.</p></article>
      </div>
    </section>

    <blockquote class="about-quote"><p>“Lai katrā dienā ir kaut kas, kas sasilda sirdi. Kaut kas, ar ko dalīties. Kaut kas mazs, bet neaizmirstams.”</p><cite>Ar siltumu — ShocoStore</cite></blockquote>

    <section class="about-invite" aria-labelledby="about-invite-title">
      <span class="eyebrow">Tavs saldais mirklis sākas šeit</span>
      <h2 id="about-invite-title">Atrodi garšu.<br />Radīsim atmiņas.</h2>
      <p>Paldies, ka ļauj ShocoStore kļūt par mazu daļu no savas dienas. Lai mūsu satikšanās atstāj smaidu un vēlmi dzīvi izgaršot mazliet lēnāk.</p>
      <a class="button pink-button" href="#/veikals">Atklāj savu šokolādi ↗</a>
    </section>
  </main>
</template>

<style scoped>
.about-page{padding-block:28px 70px}.about-back{display:inline-block;font-size:14px;color:#806c5d;margin-bottom:35px}.about-back:hover{color:var(--pink)}.about-intro{text-align:center;max-width:850px;margin:0 auto 48px}.about-intro h1{font-size:clamp(36px,5vw,64px);line-height:1.08;letter-spacing:-1.8px;margin:24px 0}.about-intro>p{max-width:650px;margin:auto;color:#806c5d;font-size:17px;line-height:1.85}.about-heart{background:var(--brown);color:var(--cream);padding:clamp(28px,5vw,60px);border-radius:45px}.about-kicker{color:#77dcc2;font-size:12px;letter-spacing:1.5px;line-height:1.7}.about-heart h2{font-size:clamp(29px,3.5vw,44px);line-height:1.15;margin:18px 0 26px;max-width:640px}.about-heart h2 span{color:var(--yellow)}.about-heart p{max-width:760px;font-size:16px;line-height:1.9;color:#efdfcd}.about-heart p+p{margin-top:18px}.about-values{padding-block:55px}.about-values>h2{font-size:34px;margin-bottom:25px}.about-value-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.about-value-grid article{padding:28px;border:1px solid #d9c4ae;background:#fffaf1;border-radius:25px}.about-value-grid article:nth-child(2){background:#fff0f6}.about-value-grid article:nth-child(3){background:#e6f7ed}.about-number{font-size:12px;color:#806c5d;letter-spacing:1px}.about-value-grid h3{font-size:24px;line-height:1.15;margin:18px 0 14px}.about-value-grid p{font-size:15px;line-height:1.8;color:#806c5d}.about-quote{margin:0;padding:35px 20px;text-align:center;border-block:1px dashed #bca784}.about-quote p{font-family:'Outfit',sans-serif;font-size:clamp(25px,3vw,37px);line-height:1.4;max-width:780px;margin:auto}.about-quote cite{display:block;margin-top:20px;color:#806c5d;font-style:normal;font-size:14px}.about-invite{text-align:center;padding-top:55px}.about-invite h2{font-size:clamp(34px,4vw,48px);line-height:1.12;margin:22px 0}.about-invite p{max-width:580px;margin:0 auto 24px;color:#806c5d;font-size:16px;line-height:1.85}.about-invite .button{font-size:14px;padding:14px 24px}@media(max-width:800px){.about-value-grid{grid-template-columns:1fr}.about-intro>p{font-size:16px}.about-page{padding-bottom:45px}.about-heart{border-radius:28px}}@media(prefers-reduced-motion:reduce){.about-invite .button{transition:none}}
</style>