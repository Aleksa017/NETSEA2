<?php
require 'config.php';

$utente_loggato = isset($_SESSION['id']) ? [
    'nome'    => $_SESSION['nome']    ?? '',
    'cognome' => $_SESSION['cognome'] ?? '',
    'ruolo'   => $_SESSION['ruolo']   ?? 'utente',
] : null;
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NetSea — Ecosistemi Marini</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    /* ══════════════════════════════════════════
       ROOT & RESET
    ══════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --ink:     #04111e;
      --deep:    #071e33;
      --ocean:   #0b3d5e;
      --mid:     #1267a0;
      --wave:    #1b9fd4;
      --foam:    #72d7f0;
      --pearl:   #e8f6fc;
      --sand:    #f5ede0;
      --sand2:   #e8d9c4;
      --coral:   #e05a3a;
      --kelp:    #2cb89b;
      --gold:    #f0c040;
      --text:    #c5e4f5;
      --muted:   #5d9ab8;
      --ease:    cubic-bezier(.25,.46,.45,.94);
    }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Outfit', sans-serif;
      background: var(--ink);
      color: var(--text);
      overflow-x: hidden;
      cursor: none;
    }

    /* ── CUSTOM CURSOR ── */
    .cursor {
      width: 12px; height: 12px;
      background: var(--foam);
      border-radius: 50%;
      position: fixed; top: 0; left: 0;
      pointer-events: none; z-index: 9999;
      transform: translate(-50%,-50%);
      transition: width .2s, height .2s, background .2s;
      mix-blend-mode: screen;
    }
    .cursor-ring {
      width: 36px; height: 36px;
      border: 1.5px solid rgba(114,215,240,0.5);
      border-radius: 50%;
      position: fixed; top: 0; left: 0;
      pointer-events: none; z-index: 9998;
      transform: translate(-50%,-50%);
      transition: transform .12s var(--ease), width .3s, height .3s, opacity .3s;
    }
    body:has(a:hover) .cursor, body:has(button:hover) .cursor { width:20px; height:20px; background:var(--wave); }

    /* ══════════════════════════════════════════
       NAVBAR
    ══════════════════════════════════════════ */
    nav {
      position: fixed; top: 0; left: 0; right: 0;
      z-index: 500;
      height: 68px;
      display: flex; align-items: center;
      padding: 0 2.5rem;
      gap: 1rem;
      background: linear-gradient(180deg, rgba(4,17,30,.95) 0%, rgba(4,17,30,0) 100%);
      backdrop-filter: blur(0px);
      transition: backdrop-filter .4s, background .4s;
    }
    nav.scrolled {
      background: rgba(4,17,30,0.92);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(114,215,240,.1);
    }

    .nav-logo {
      display: flex; align-items: center; gap: .6rem;
      text-decoration: none;
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.75rem;
      font-weight: 600;
      color: var(--pearl);
      letter-spacing: .04em;
      flex-shrink: 0;
    }
    .nav-logo-wave {
      width: 36px; height: 36px;
      position: relative; flex-shrink: 0;
    }
    .nav-logo-wave svg { width: 100%; height: 100%; }

    .nav-links {
      display: flex; list-style: none; gap: .25rem;
      margin-left: 2rem;
    }
    .nav-links a {
      padding: .45rem 1rem;
      border-radius: 6px;
      color: var(--muted);
      text-decoration: none;
      font-size: .875rem;
      font-weight: 500;
      letter-spacing: .02em;
      transition: color .2s, background .2s;
    }
    .nav-links a:hover { color: var(--foam); background: rgba(114,215,240,.08); }

    /* SEARCH BAR */
    .nav-search {
      flex: 1;
      max-width: 520px;
      margin: 0 auto;
      position: relative;
    }
    .nav-search input {
      width: 100%;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(114,215,240,.15);
      border-radius: 40px;
      padding: .55rem 1.25rem .55rem 3rem;
      color: var(--pearl);
      font-family: 'Outfit', sans-serif;
      font-size: .875rem;
      outline: none;
      transition: border-color .2s, background .2s, box-shadow .2s;
    }
    .nav-search input::placeholder { color: var(--muted); }
    .nav-search input:focus {
      background: rgba(114,215,240,.08);
      border-color: var(--wave);
      box-shadow: 0 0 0 3px rgba(27,159,212,.15);
    }
    .nav-search .icon {
      position: absolute; left: 1.1rem; top: 50%;
      transform: translateY(-50%);
      color: var(--muted); font-size: 1rem;
      pointer-events: none;
    }
    .nav-search .clear-btn {
      position: absolute; right: 1rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: var(--muted); cursor: pointer; font-size: 1.1rem;
      display: none;
    }

    .nav-actions { display: flex; align-items: center; gap: .75rem; margin-left: auto; flex-shrink: 0; }
    .nav-icon-btn {
      width: 40px; height: 40px;
      border-radius: 50%;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(114,215,240,.12);
      display: flex; align-items: center; justify-content: center;
      color: var(--muted); cursor: pointer; text-decoration: none;
      font-size: 1.15rem;
      transition: background .2s, color .2s, border-color .2s;
    }
    .nav-icon-btn:hover { background: rgba(114,215,240,.12); color: var(--foam); border-color: rgba(114,215,240,.3); }

    /* ══════════════════════════════════════════
       SEARCH RESULTS OVERLAY
    ══════════════════════════════════════════ */
    .search-overlay {
      position: fixed; inset: 0; z-index: 400;
      background: rgba(4,17,30,.92);
      backdrop-filter: blur(24px);
      display: none;
      overflow-y: auto;
      padding: 100px 2rem 4rem;
    }
    .search-overlay.active { display: block; animation: fadeIn .25s ease; }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }

    .search-overlay-inner { max-width: 900px; margin: 0 auto; }
    .search-close {
      position: fixed; top: 20px; right: 2rem;
      background: rgba(114,215,240,.1); border: 1px solid rgba(114,215,240,.2);
      border-radius: 50%; width: 44px; height: 44px;
      color: var(--foam); font-size: 1.2rem; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
    }
    .search-query-label {
      color: var(--muted); font-size: .875rem; margin-bottom: 1.5rem;
    }
    .search-query-label strong { color: var(--foam); }

    /* PINNED SPECIES CARD */
    .pinned-card {
      background: linear-gradient(135deg, rgba(11,61,94,.6), rgba(7,30,51,.8));
      border: 1px solid rgba(114,215,240,.2);
      border-radius: 16px;
      padding: 1.5rem;
      display: grid;
      grid-template-columns: 1fr 160px;
      gap: 1.5rem;
      align-items: center;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    .pinned-card::before {
      content: '📌 Specie trovata';
      position: absolute; top: 1rem; left: 1.5rem;
      font-size: .72rem; letter-spacing: .1em; text-transform: uppercase;
      color: var(--wave); background: rgba(27,159,212,.12);
      border: 1px solid rgba(27,159,212,.2);
      padding: .2rem .7rem; border-radius: 20px;
    }
    .pinned-card-info { padding-top: 1.5rem; }
    .pinned-card-info h2 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.8rem; color: var(--pearl);
      margin-bottom: .25rem;
    }
    .pinned-card-info .scientific { font-style: italic; color: var(--muted); font-size: .9rem; margin-bottom: .75rem; }
    .pinned-card-info p { color: var(--text); font-size: .9rem; line-height: 1.6; margin-bottom: 1rem; }
    .pinned-card-img {
      width: 160px; height: 160px;
      background: linear-gradient(135deg, var(--ocean), var(--deep));
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 5rem;
      border: 1px solid rgba(114,215,240,.1);
    }
    .badge { display: inline-block; padding: .2rem .65rem; border-radius: 4px; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; margin-right: .4rem; }
    .badge-cr { background: rgba(224,90,58,.2); color: #e8836a; border: 1px solid rgba(224,90,58,.3); }
    .badge-en { background: rgba(224,90,58,.15); color: #e0a060; border: 1px solid rgba(224,90,58,.25); }
    .badge-vu { background: rgba(240,192,64,.15); color: var(--gold); border: 1px solid rgba(240,192,64,.25); }
    .badge-lc { background: rgba(44,184,155,.15); color: var(--kelp); border: 1px solid rgba(44,184,155,.25); }

    .btn-outline {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .55rem 1.25rem; border-radius: 8px;
      border: 1px solid rgba(114,215,240,.3);
      color: var(--foam); background: rgba(114,215,240,.06);
      text-decoration: none; font-size: .875rem; font-weight: 500;
      cursor: pointer; transition: all .2s;
    }
    .btn-outline:hover { background: rgba(114,215,240,.14); border-color: var(--foam); }
    .btn-solid {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .55rem 1.25rem; border-radius: 8px;
      background: var(--wave); color: var(--ink);
      text-decoration: none; font-size: .875rem; font-weight: 600;
      cursor: pointer; border: none; transition: all .2s;
    }
    .btn-solid:hover { background: var(--foam); transform: translateY(-1px); }

    /* SEARCH RESULTS SECTIONS */
    .results-section { margin-bottom: 2.5rem; }
    .results-section-title {
      font-size: .8rem; letter-spacing: .1em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 1rem;
      display: flex; align-items: center; gap: .75rem;
    }
    .results-section-title::after { content: ''; flex: 1; height: 1px; background: rgba(114,215,240,.1); }

    /* SORT DROPDOWN */
    .sort-row { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .sort-row label { font-size: .8rem; color: var(--muted); }
    .sort-row select {
      background: rgba(11,61,94,.4); border: 1px solid rgba(114,215,240,.2);
      border-radius: 8px; color: var(--foam); font-family: 'Outfit',sans-serif;
      font-size: .8rem; padding: .4rem .8rem; outline: none; cursor: pointer;
    }
    .sort-row select option { background: var(--deep); }

    .result-row {
      background: rgba(11,61,94,.25);
      border: 1px solid rgba(114,215,240,.08);
      border-radius: 12px; padding: 1rem 1.25rem;
      display: flex; align-items: center; gap: 1rem;
      margin-bottom: .75rem; cursor: pointer;
      transition: border-color .2s, background .2s, transform .2s;
      text-decoration: none;
    }
    .result-row:hover { background: rgba(11,61,94,.5); border-color: rgba(114,215,240,.2); transform: translateX(4px); }
    .result-row-icon { font-size: 1.8rem; flex-shrink: 0; width: 44px; text-align: center; }
    .result-row h4 { color: var(--pearl); font-size: .95rem; font-weight: 500; margin-bottom: .2rem; }
    .result-row p { color: var(--muted); font-size: .82rem; }
    .result-row-meta { margin-left: auto; text-align: right; flex-shrink: 0; }
    .result-row-meta .date { font-size: .75rem; color: var(--muted); }
    .result-row-meta .author { font-size: .78rem; color: var(--wave); }
    .no-results { text-align: center; padding: 3rem; color: var(--muted); }

    /* ══════════════════════════════════════════
       HERO / NEWS CAROUSEL
    ══════════════════════════════════════════ */
    .hero {
      height: 100vh; min-height: 620px;
      position: relative; overflow: hidden;
    }

    /* CAROUSEL SLIDES */
    .carousel-track {
      display: flex; height: 100%;
      transition: transform .9s cubic-bezier(.77,0,.175,1);
    }
    .slide {
      min-width: 100%; height: 100%; position: relative; flex-shrink: 0;
    }
    .slide-bg {
      position: absolute; inset: 0;
      background-size: cover; background-position: center;
    }
    .slide-bg::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(
        to right,
        rgba(4,17,30,.92) 0%,
        rgba(4,17,30,.6) 50%,
        rgba(4,17,30,.1) 100%
      );
    }
    .slide-content {
      position: absolute; inset: 0;
      display: flex; flex-direction: column;
      justify-content: flex-end;
      padding: 5rem 5rem 5rem 5rem;
      max-width: 680px;
    }
    .slide-tag {
      display: inline-block;
      padding: .3rem .9rem;
      border-radius: 20px;
      font-size: .72rem; letter-spacing: .1em; text-transform: uppercase;
      background: rgba(27,159,212,.2);
      border: 1px solid rgba(27,159,212,.35);
      color: var(--wave);
      margin-bottom: 1rem;
      width: fit-content;
    }
    .slide-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2rem, 4vw, 3.2rem);
      font-weight: 400; line-height: 1.15;
      color: var(--pearl); margin-bottom: .85rem;
    }
    .slide-desc {
      font-size: .95rem; color: rgba(197,228,245,.75);
      line-height: 1.65; margin-bottom: 1.5rem;
      max-width: 480px;
    }
    .slide-author {
      font-size: .8rem; color: var(--muted); margin-bottom: 1.25rem;
    }
    .slide-author span { color: var(--wave); }
    .slide-btn {
      display: inline-flex; align-items: center; gap: .6rem;
      padding: .75rem 1.75rem;
      background: rgba(255,255,255,.9);
      color: var(--ink); border-radius: 40px;
      text-decoration: none; font-weight: 600; font-size: .9rem;
      width: fit-content;
      transition: background .2s, transform .2s;
    }
    .slide-btn:hover { background: #fff; transform: scale(1.03); }

    /* CAROUSEL CONTROLS */
    .carousel-dots {
      position: absolute; bottom: 2rem; left: 50%;
      transform: translateX(-50%);
      display: flex; gap: .5rem; z-index: 10;
    }
    .dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: rgba(255,255,255,.35); cursor: pointer;
      transition: background .3s, transform .3s;
      border: none;
    }
    .dot.active { background: #fff; transform: scale(1.4); }

    .carousel-arrow {
      position: absolute; top: 50%; z-index: 10;
      transform: translateY(-50%);
      width: 44px; height: 44px; border-radius: 50%;
      background: rgba(255,255,255,.1);
      border: 1px solid rgba(255,255,255,.2);
      color: #fff; font-size: 1.1rem; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s;
      backdrop-filter: blur(10px);
    }
    .carousel-arrow:hover { background: rgba(255,255,255,.2); }
    .carousel-arrow.prev { left: 1.5rem; }
    .carousel-arrow.next { right: 1.5rem; }

    /* SLIDE STRIP (small thumbnails right side) */
    .slide-strip {
      position: absolute; right: 2rem; top: 50%;
      transform: translateY(-50%);
      display: flex; flex-direction: column; gap: .75rem;
      z-index: 10;
    }
    .strip-thumb {
      width: 120px; height: 70px;
      border-radius: 10px; overflow: hidden;
      border: 2px solid rgba(255,255,255,.15);
      cursor: pointer; opacity: .5;
      transition: opacity .3s, border-color .3s, transform .3s;
      flex-shrink: 0;
      background: var(--deep);
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
    }
    .strip-thumb.active { opacity: 1; border-color: var(--foam); transform: scale(1.05); }
    .strip-thumb:hover { opacity: .8; }

    /* ══════════════════════════════════════════
       MAIN SECTIONS WRAPPER
    ══════════════════════════════════════════ */
    main { position: relative; z-index: 1; }
    section { padding: 5rem 2.5rem; }
    .container { max-width: 1200px; margin: 0 auto; }

    .section-eyebrow {
      font-size: .75rem; letter-spacing: .14em; text-transform: uppercase;
      color: var(--wave); margin-bottom: .6rem;
    }
    .section-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(1.8rem, 3vw, 2.8rem);
      color: var(--pearl); font-weight: 400; margin-bottom: .75rem;
    }
    .section-sub { color: var(--muted); font-size: .95rem; max-width: 500px; }

    /* ══════════════════════════════════════════
       DONATIONS SECTION
    ══════════════════════════════════════════ */
    .donations-section {
      background: rgba(7,30,51,.4);
      border-top: 1px solid rgba(114,215,240,.07);
      border-bottom: 1px solid rgba(114,215,240,.07);
    }

    .donations-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
    .donations-filters { display: flex; gap: .5rem; flex-wrap: wrap; }
    .filter-chip {
      padding: .4rem 1rem; border-radius: 20px;
      background: rgba(11,61,94,.4); border: 1px solid rgba(114,215,240,.15);
      color: var(--muted); font-size: .8rem; cursor: pointer;
      transition: all .2s;
    }
    .filter-chip.active, .filter-chip:hover { background: rgba(27,159,212,.15); border-color: rgba(27,159,212,.4); color: var(--foam); }

    .donation-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem; }
    .donation-card {
      background: rgba(11,61,94,.25);
      border: 1px solid rgba(114,215,240,.1);
      border-radius: 14px; overflow: hidden;
      transition: transform .25s, border-color .25s, box-shadow .25s;
    }
    .donation-card:hover {
      transform: translateY(-5px);
      border-color: rgba(114,215,240,.25);
      box-shadow: 0 16px 48px rgba(27,159,212,.1);
    }
    .donation-card-top {
      height: 140px;
      background: linear-gradient(135deg, var(--ocean), var(--deep));
      display: flex; align-items: center; justify-content: center;
      font-size: 4rem; position: relative;
    }
    .donation-status {
      position: absolute; top: .75rem; right: .75rem;
      padding: .2rem .65rem; border-radius: 20px;
      font-size: .7rem; font-weight: 600; text-transform: uppercase;
      background: rgba(44,184,155,.2); color: var(--kelp);
      border: 1px solid rgba(44,184,155,.3);
    }
    .donation-card-body { padding: 1.25rem; }
    .donation-card-body h3 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.15rem; color: var(--pearl); margin-bottom: .4rem; line-height: 1.3;
    }
    .donation-card-body p { color: var(--muted); font-size: .83rem; line-height: 1.5; margin-bottom: 1rem; }
    .progress-bar { background: rgba(255,255,255,.08); border-radius: 20px; height: 6px; overflow: hidden; margin-bottom: .5rem; }
    .progress-fill { height: 100%; border-radius: 20px; background: linear-gradient(90deg, var(--kelp), var(--wave)); transition: width 1.2s var(--ease); }
    .progress-meta { display: flex; justify-content: space-between; font-size: .78rem; color: var(--muted); margin-bottom: 1rem; }
    .progress-meta strong { color: var(--kelp); }

    /* ══════════════════════════════════════════
       FEED SECTION (TikTok-style)
    ══════════════════════════════════════════ */
    .feed-section {
      background: linear-gradient(180deg, var(--ink) 0%, rgba(7,30,51,.3) 100%);
    }
    .feed-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; }
    .feed-scroll-track {
      display: flex; gap: 1rem;
      overflow-x: auto; padding-bottom: 1rem;
      scrollbar-width: thin; scrollbar-color: rgba(114,215,240,.2) transparent;
      scroll-snap-type: x mandatory;
    }
    .feed-scroll-track::-webkit-scrollbar { height: 4px; }
    .feed-scroll-track::-webkit-scrollbar-track { background: transparent; }
    .feed-scroll-track::-webkit-scrollbar-thumb { background: rgba(114,215,240,.2); border-radius: 2px; }

    .feed-card {
      min-width: 220px; height: 380px;
      border-radius: 16px; overflow: hidden;
      position: relative; flex-shrink: 0;
      scroll-snap-align: start;
      cursor: pointer;
      border: 1px solid rgba(114,215,240,.1);
      transition: transform .25s, box-shadow .25s;
    }
    .feed-card:hover { transform: scale(1.03); box-shadow: 0 20px 60px rgba(0,0,0,.4); }
    .feed-card-bg {
      position: absolute; inset: 0;
      background: linear-gradient(135deg, var(--ocean) 0%, var(--deep) 100%);
      display: flex; align-items: center; justify-content: center;
      font-size: 5rem;
    }
    .feed-card-overlay {
      position: absolute; bottom: 0; left: 0; right: 0;
      background: linear-gradient(transparent, rgba(4,17,30,.95));
      padding: 1.25rem 1rem 1rem;
    }
    .feed-card-type {
      font-size: .68rem; letter-spacing: .1em; text-transform: uppercase;
      color: var(--wave); margin-bottom: .4rem;
    }
    .feed-card-title { font-size: .9rem; color: var(--pearl); font-weight: 500; line-height: 1.3; margin-bottom: .5rem; }
    .feed-card-author { font-size: .75rem; color: var(--muted); }
    .feed-card-play {
      position: absolute; top: 1rem; right: 1rem;
      width: 36px; height: 36px; border-radius: 50%;
      background: rgba(255,255,255,.15);
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem;
      backdrop-filter: blur(8px);
    }
    .feed-card:has(.video-indicator) .feed-card-play { display: flex; }

    /* ══════════════════════════════════════════
       FOOTER WITH SAND EFFECT
    ══════════════════════════════════════════ */
    .footer-wave-divider {
      position: relative; height: 160px; overflow: hidden;
      background: var(--ink);
    }
    .footer-wave-divider svg { position: absolute; bottom: 0; width: 100%; }

    footer {
      background: var(--sand);
      position: relative;
      overflow: hidden;
      padding: 4rem 2.5rem 2rem;
    }
    footer::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse 80% 60% at 50% 100%, rgba(232,217,196,.6) 0%, transparent 70%),
        repeating-linear-gradient(
          90deg,
          transparent 0px, transparent 3px,
          rgba(210,185,155,.15) 3px, rgba(210,185,155,.15) 4px
        );
    }
    /* shallow water gradient at top of footer */
    footer::after {
      content: '';
      position: absolute; top: -20px; left: 0; right: 0; height: 60px;
      background: linear-gradient(180deg, rgba(27,159,212,.15), transparent);
      pointer-events: none;
    }

    .footer-inner { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; }
    .footer-grid {
      display: grid;
      grid-template-columns: 2fr repeat(3, 1fr);
      gap: 3rem; margin-bottom: 3rem;
    }
    .footer-brand .logo {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.6rem; color: var(--deep);
      font-weight: 600; margin-bottom: .75rem;
      display: block;
    }
    .footer-brand p { color: #7a6a58; font-size: .875rem; line-height: 1.7; }
    .footer-col h4 { color: var(--deep); font-size: .75rem; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 1rem; }
    .footer-col ul { list-style: none; }
    .footer-col li { margin-bottom: .5rem; }
    .footer-col a { color: #9a8a78; font-size: .875rem; text-decoration: none; transition: color .2s; }
    .footer-col a:hover { color: var(--ocean); }

    .footer-bottom {
      border-top: 1px solid rgba(120,100,80,.2);
      padding-top: 1.5rem;
      display: flex; justify-content: space-between; align-items: center;
      flex-wrap: wrap; gap: 1rem;
    }
    .footer-bottom p { color: #9a8a78; font-size: .8rem; }

    /* Tiny shells decoration */
    .shell-decor {
      position: absolute; bottom: 1rem;
      font-size: 1.5rem; opacity: .4;
    }

    /* ══════════════════════════════════════════
       WAVE SEPARATOR
    ══════════════════════════════════════════ */
    .wave-sep {
      width: 100%; overflow: hidden; line-height: 0;
      background: var(--ink);
    }
    .wave-sep svg { display: block; }

    /* ══════════════════════════════════════════
       STATS ROW
    ══════════════════════════════════════════ */
    .stats-row {
      display: flex; justify-content: center; gap: 5rem;
      padding: 2rem 0; flex-wrap: wrap;
      border-top: 1px solid rgba(114,215,240,.07);
      border-bottom: 1px solid rgba(114,215,240,.07);
      background: rgba(7,30,51,.3);
    }
    .stat-item { text-align: center; }
    .stat-num {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.2rem; color: var(--foam); display: block; font-weight: 600;
    }
    .stat-lbl { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }

    /* ══════════════════════════════════════════
       LOADING / ANIMATIONS
    ══════════════════════════════════════════ */
    @keyframes slideUp { from{opacity:0;transform:translateY(28px)} to{opacity:1;transform:translateY(0)} }
    .anim { opacity: 0; }
    .anim.visible { animation: slideUp .65s var(--ease) forwards; }
    .anim-d1 { animation-delay: .1s; }
    .anim-d2 { animation-delay: .2s; }
    .anim-d3 { animation-delay: .35s; }

    /* ══════════════════════════════════════════
       RESPONSIVE
    ══════════════════════════════════════════ */
    @media (max-width: 900px) {
      .nav-links { display: none; }
      .slide-content { padding: 4rem 2rem 4rem 2rem; }
      .slide-strip { display: none; }
      .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 600px) {
      .footer-grid { grid-template-columns: 1fr; }
      .stats-row { gap: 2rem; }
    }

    /* PHP PLACEHOLDER COMMENT STYLE */
    .php-placeholder {
      font-size: .7rem; color: rgba(114,215,240,.3);
      font-family: monospace; padding: .2rem;
      display: none; /* visibile in dev mode */
    }

    /* ── USER DROPDOWN ── */
    .user-btn-wrap { position: relative; }

    .user-dropdown {
      position: absolute; top: calc(100% + 12px); right: 0;
      width: 260px;
      background: rgba(7,30,51,0.97);
      border: 1px solid rgba(114,215,240,.18);
      border-radius: 16px;
      box-shadow: 0 24px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(114,215,240,.05);
      backdrop-filter: blur(24px);
      overflow: hidden;
      opacity: 0; pointer-events: none;
      transform: translateY(-8px) scale(.97);
      transform-origin: top right;
      transition: opacity .2s var(--ease), transform .2s var(--ease);
      z-index: 600;
    }
    .user-dropdown.open {
      opacity: 1; pointer-events: all;
      transform: translateY(0) scale(1);
    }

    .user-drop-top {
      padding: 1.25rem 1.25rem .9rem;
      border-bottom: 1px solid rgba(114,215,240,.08);
      text-align: center;
    }
    .user-drop-top .avatar {
      width: 52px; height: 52px; border-radius: 50%;
      background: linear-gradient(135deg, var(--ocean), var(--mid));
      border: 2px solid rgba(114,215,240,.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; margin: 0 auto .6rem;
    }
    .user-drop-top p { color: var(--muted); font-size: .78rem; }
    .user-drop-top strong { color: var(--foam); font-size: .88rem; }

    .user-drop-actions {
      padding: .9rem 1rem;
      display: flex; flex-direction: column; gap: .5rem;
    }
    .drop-btn {
      display: flex; align-items: center; gap: .75rem;
      padding: .7rem 1rem; border-radius: 10px;
      text-decoration: none; font-size: .875rem; font-weight: 500;
      cursor: pointer; transition: all .2s; border: none;
      font-family: 'Outfit', sans-serif; width: 100%;
    }
    .drop-btn-primary {
      background: var(--wave); color: var(--ink); font-weight: 600;
      justify-content: center;
    }
    .drop-btn-primary:hover { background: var(--foam); transform: translateY(-1px); }
    .drop-btn-secondary {
      background: rgba(114,215,240,.07);
      border: 1px solid rgba(114,215,240,.15);
      color: var(--foam);
      justify-content: center;
    }
    .drop-btn-secondary:hover { background: rgba(114,215,240,.14); border-color: rgba(114,215,240,.3); }
    .drop-btn-ghost {
      background: none; color: var(--muted);
      font-size: .8rem; font-weight: 400;
      justify-content: center;
      padding: .4rem;
    }
    .drop-btn-ghost:hover { color: var(--foam); }

    .drop-divider {
      height: 1px; background: rgba(114,215,240,.08);
      margin: .25rem 0;
    }
    .drop-link {
      display: flex; align-items: center; gap: .75rem;
      padding: .6rem 1rem; border-radius: 8px;
      text-decoration: none; color: var(--muted); font-size: .82rem;
      transition: background .2s, color .2s;
    }
    .drop-link:hover { background: rgba(114,215,240,.07); color: var(--foam); }
    .drop-link .icon { font-size: 1rem; width: 20px; text-align: center; }

    /* Arrow pointer on dropdown */
    .user-dropdown::before {
      content: '';
      position: absolute; top: -6px; right: 14px;
      width: 12px; height: 12px;
      background: rgba(7,30,51,0.97);
      border-left: 1px solid rgba(114,215,240,.18);
      border-top: 1px solid rgba(114,215,240,.18);
      transform: rotate(45deg);
    }
  </style>
</head>
<body>

<!-- CUSTOM CURSOR -->
<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<!-- ══════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════ -->
<nav id="navbar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-wave">
      <svg viewBox="0 0 40 40" fill="none">
        <circle cx="20" cy="20" r="18" fill="rgba(27,159,212,.15)" stroke="rgba(114,215,240,.3)" stroke-width="1"/>
        <path d="M8 22 Q12 16 16 22 Q20 28 24 22 Q28 16 32 22" stroke="#72d7f0" stroke-width="2" fill="none" stroke-linecap="round"/>
        <path d="M8 18 Q12 12 16 18 Q20 24 24 18 Q28 12 32 18" stroke="rgba(114,215,240,.45)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
      </svg>
    </div>
    NetSea
  </a>

  <ul class="nav-links">
    <li><a href="#news">News</a></li>
    <li><a href="#donazioni">Donazioni</a></li>
    <li><a href="#feed">Scopri</a></li>
    <li><a href="Specie.php">Specie</a></li>
    <li><a href="Luoghi.php">Luoghi</a></li>
  </ul>

  <!-- SEARCH -->
  <div class="nav-search">
    <span class="icon">🔍</span>
    <input type="text" id="searchInput" placeholder="Cerca specie, habitat, ricerche…" autocomplete="off">
    <button class="clear-btn" id="clearBtn" onclick="clearSearch()">✕</button>
  </div>

  <div class="nav-actions">
    <!-- USER DROPDOWN TRIGGER -->
    <div class="user-btn-wrap" id="userBtnWrap">
      <button class="nav-icon-btn" id="userBtn" title="Accedi o registrati" onclick="toggleDropdown(event)">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
          <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
      </button>
      <?php if ($utente_loggato): ?>
      <div class="user-dropdown" id="userDropdown">
        <div class="user-drop-top">
          <div class="avatar">
            <?php
              if($utente_loggato['ruolo']==='admin') echo '⚙️';
              elseif($utente_loggato['ruolo']==='ricercatore') echo '🔬';
              else echo '👤';
            ?>
          </div>
          <strong><?= htmlspecialchars($utente_loggato['nome'].' '.$utente_loggato['cognome']) ?></strong>
          <p><?= htmlspecialchars(ucfirst($utente_loggato['ruolo'])) ?></p>
        </div>
        <div class="user-drop-actions">
    <a href="profilo.php" class="drop-link"><span class="icon">👤</span> Il mio profilo</a>
    
    <?php if(isset($_SESSION['ruolo']) && in_array($_SESSION['ruolo'], ['ricercatore', 'admin'])): ?>
        <a href="crea_news.php" class="drop-link"><span class="icon">📰</span> Pubblica news</a>
        <a href="crea_contenuto.php" class="drop-link"><span class="icon">✏️</span> Crea contenuto</a>
    <?php endif; ?>

    <?php if(isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin'): ?>
        <a href="admin.php" class="drop-link"><span class="icon">⚙️</span> Pannello Admin</a>
    <?php endif; ?>

    <div class="drop-divider"></div>
    <a href="logout.php" class="drop-btn drop-btn-ghost">🚪 Esci</a>
</div>
      </div>
      <?php else: ?>
      <div class="user-dropdown" id="userDropdown">
        <div class="user-drop-top">
          <div class="avatar">🌊</div>
          <strong>Benvenuto su NetSea</strong>
          <p>Accedi per feed personalizzato e donazioni</p>
        </div>
        <div class="user-drop-actions">
          <a href="Login.php" class="drop-btn drop-btn-primary">Accedi</a>
          <a href="Registrazione.php" class="drop-btn drop-btn-secondary">✨ Crea account</a>
          <div class="drop-divider"></div>
          <a href="Registrazione.php?tipo=ricercatore" class="drop-link"><span class="icon">🔬</span> Richiedi account ricercatore</a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ══════════════════════════════════════════
     SEARCH OVERLAY
══════════════════════════════════════════ -->
<div class="search-overlay" id="searchOverlay">
  <button class="search-close" onclick="closeSearch()">✕</button>
  <div class="search-overlay-inner">
    <p class="search-query-label">Risultati per: <strong id="queryDisplay"></strong></p>
    <!-- Tutto viene generato dal JS -->
    <div id="searchResults"></div>
  </div>
</div>

<!-- ══════════════════════════════════════════
     HERO — NEWS CAROUSEL
══════════════════════════════════════════ -->
<!-- PHP: $news = fetch last 10 news from DB -->
<section class="hero" id="news">
  <div class="carousel-track" id="carouselTrack">

    <!-- SLIDE 1 -->
    <!-- PHP: foreach($news as $n): -->
    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #062040 0%, #0a4060 40%, #0b5575 100%);">
        <!-- real: background-image: url('<?= $n['copertina'] ?>')  -->
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.15;user-select:none;">🌊</div>
      </div>
      <div class="slide-content">
        <span class="slide-tag">🌡️ Clima</span>
        <h1 class="slide-title">Temperatura degli oceani raggiunge il record storico nel 2024</h1>
        <p class="slide-desc">I ricercatori confermano un aumento di 0.3°C rispetto alla media del secolo. Le conseguenze per la biodiversità marina potrebbero essere irreversibili.</p>
        <p class="slide-author">Di <span>Dr. Maria Ferretti</span> · CNR-ISMAR · 15 Feb 2025</p>
        <a href="news.php?id=1" class="slide-btn">Leggi l'articolo →</a>
      </div>
    </div>

    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #031a10 0%, #0a3d20 40%, #0d5530 100%);">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.12;user-select:none;">🪸</div>
      </div>
      <div class="slide-content">
        <span class="slide-tag">🪸 Coralli</span>
        <h1 class="slide-title">Sbiancamento di massa: il 60% della Grande Barriera Corallina colpita</h1>
        <p class="slide-desc">La quarta ondata di sbiancamento massiccio in 8 anni ha investito le barriere coralline australiane. I biologi parlano di punto di non ritorno.</p>
        <p class="slide-author">Di <span>Prof. James Cook</span> · Univ. Queensland · 12 Feb 2025</p>
        <a href="news.php?id=2" class="slide-btn">Leggi l'articolo →</a>
      </div>
    </div>

    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #1a0f30 0%, #2a1050 40%, #1a0a40 100%);">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.12;user-select:none;">🦑</div>
      </div>
      <div class="slide-content">
        <span class="slide-tag">🔬 Scoperta</span>
        <h1 class="slide-title">Nuova specie di cefalopode bioluminescente scoperta nel Mar Ionio</h1>
        <p class="slide-desc">Il team del CNR-ISMAR ha identificato nelle profondità ioniche una specie di polpo mai catalogata prima, capace di emettere luce blu-verde.</p>
        <p class="slide-author">Di <span>Dr.ssa Lucia Romano</span> · CNR-ISMAR · 8 Feb 2025</p>
        <a href="news.php?id=3" class="slide-btn">Leggi l'articolo →</a>
      </div>
    </div>

    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #002020 0%, #004040 40%, #005550 100%);">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.12;user-select:none;">🐋</div>
      </div>
      <div class="slide-content">
        <span class="slide-tag">💚 Conservazione</span>
        <h1 class="slide-title">La popolazione di balene azzurre mostra segni di ripresa nell'Atlantico</h1>
        <p class="slide-desc">Dopo decenni di caccia intensiva, il censimento 2024 rivela un aumento del 12% degli esemplari nel Nord Atlantico: una rara buona notizia per gli oceani.</p>
        <p class="slide-author">Di <span>Dr. Marco Bianchi</span> · OGS Trieste · 3 Feb 2025</p>
        <a href="news.php?id=4" class="slide-btn">Leggi l'articolo →</a>
      </div>
    </div>

    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #200a00 0%, #401500 40%, #502010 100%);">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.12;user-select:none;">🧫</div>
      </div>
      <div class="slide-content">
        <span class="slide-tag">⚠️ Inquinamento</span>
        <h1 class="slide-title">Microplastiche rilevate nel sangue di cetacei del Mediterraneo</h1>
        <p class="slide-desc">Studio dell'Università di Palermo rileva concentrazioni preoccupanti di particelle di plastica in campioni ematici di delfini e balene del Tirreno.</p>
        <p class="slide-author">Di <span>Prof.ssa Anna Greco</span> · Univ. Palermo · 28 Gen 2025</p>
        <a href="news.php?id=5" class="slide-btn">Leggi l'articolo →</a>
      </div>
    </div>

    <!-- Aggiungi altri 5 slide per arrivare a 10 totali -->
    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #0a0a30 0%, #101060 40%, #0a0a50 100%);">
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:flex-end;padding-right:20%;font-size:12rem;opacity:.12;user-select:none;">🌊</div>
      </div>
      <div class="slide-content">
        <span class="slide-tag">🔬 Ricerca</span>
        <h1 class="slide-title">Acidificazione degli oceani: impatti a cascata sulle catene alimentari marine</h1>
        <p class="slide-desc">Il pH medio degli oceani è sceso di 0.1 unità rispetto all'era preindustriale. I modelli predicono effetti devastanti sulle catene trofiche entro il 2050.</p>
        <p class="slide-author">Di <span>Dr. Carlo Esposito</span> · ISPRA · 22 Gen 2025</p>
        <a href="news.php?id=6" class="slide-btn">Leggi l'articolo →</a>
      </div>
    </div>
    <!-- PHP: endforeach; -->

  </div>

  <!-- STRIP THUMBNAILS RIGHT -->
  <div class="slide-strip" id="slideStrip">
    <div class="strip-thumb active" data-idx="0">🌊</div>
    <div class="strip-thumb" data-idx="1">🪸</div>
    <div class="strip-thumb" data-idx="2">🦑</div>
    <div class="strip-thumb" data-idx="3">🐋</div>
    <div class="strip-thumb" data-idx="4">🧫</div>
    <div class="strip-thumb" data-idx="5">🔬</div>
  </div>

  <!-- ARROWS -->
  <button class="carousel-arrow prev" onclick="moveTo(currentSlide - 1)">‹</button>
  <button class="carousel-arrow next" onclick="moveTo(currentSlide + 1)">›</button>

  <!-- DOTS -->
  <div class="carousel-dots" id="carouselDots"></div>
</section>

<!-- STATS BAR -->
<div class="stats-row">
  <!-- PHP: query counts from DB -->
  <div class="stat-item anim"><span class="stat-num">2.400+</span><span class="stat-lbl">Specie Monitorate</span></div>
  <div class="stat-item anim anim-d1"><span class="stat-num">380</span><span class="stat-lbl">A Rischio</span></div>
  <div class="stat-item anim anim-d2"><span class="stat-num">48</span><span class="stat-lbl">Enti di Ricerca</span></div>
  <div class="stat-item anim anim-d3"><span class="stat-num">12k+</span><span class="stat-lbl">Utenti Attivi</span></div>
</div>

<main>
  <!-- ══════════════════════════════════════════
       DONAZIONI
  ══════════════════════════════════════════ -->
  <?php
  // carichiamo i progetti con somma delle donazioni
  $stmt = $connessione->query("\n      SELECT p.*,\n             COALESCE(SUM(d.importo),0) AS raccolto\n      FROM progetto p\n      LEFT JOIN donazione d ON d.id_pd = p.id_pd\n      GROUP BY p.id_pd\n  ");
  $progetti = $stmt->fetchAll();
  // se l'id 3 non è presente nella tabella, inseriamo il progetto di default
  $has3 = false;
  foreach ($progetti as $p) {
      if ((int)$p['id_pd'] === 3) { $has3 = true; break; }
  }
  if (!$has3) {
      try {
          $connessione->exec("INSERT IGNORE INTO progetto (id_pd, titolo, obiettivo, budget, stato, data_i) VALUES (3, 'Restauro Posidonia Adriatica', 'Reimpianto delle praterie di Posidonia oceanica lungo le coste adriatiche', 30000, 'urgente', '2026-02-10')");
      } catch (PDOException $e) {
          error_log("Unable to insert default project3: " . $e->getMessage());
      }
      // ricarichiamo dopo l'eventuale inserimento
      $stmt = $connessione->query("\n          SELECT p.*,\n                 COALESCE(SUM(d.importo),0) AS raccolto\n          FROM progetto p\n          LEFT JOIN donazione d ON d.id_pd = p.id_pd\n          GROUP BY p.id_pd\n      ");
      $progetti = $stmt->fetchAll();
  }
  ?>
  <section class="donations-section" id="donazioni">
    <div class="container">
      <div class="donations-header">
        <div>
          <p class="section-eyebrow">💚 Supporta la Ricerca</p>
          <h2 class="section-title">Progetti di Donazione</h2>
          <p class="section-sub">Finanzia direttamente la scienza che protegge i nostri oceani</p>
        </div>
        <div>
          <!-- Filters -->
          <div class="donations-filters">
            <button class="filter-chip active" onclick="filterDonations('all', this)">Tutti</button>
            <button class="filter-chip" onclick="filterDonations('attivo', this)">Attivi</button>
            <button class="filter-chip" onclick="filterDonations('completato', this)">Completati</button>
            <button class="filter-chip" onclick="filterDonations('urgente', this)">Urgenti</button>
          </div>
        </div>
      </div>

      <div class="donation-grid" id="donationGrid">
        <?php
        $icons = [1=>'🦭',2=>'🐬',3=>'🌿'];
        foreach($progetti as $p):
            $stato = $p['stato'] ?? 'attivo';
            $raccolto = floatval($p['raccolto']);
            $budget = floatval($p['budget']);
            $pct = $budget > 0 ? min(100, round($raccolto / $budget * 100)) : 0;
            $icon = $icons[$p['id_pd']] ?? '🐬';
            $topStyle = '';
            $statusStyle = '';
            if ($stato === 'urgente') {
                $topStyle = 'background:linear-gradient(135deg,#3d1a00,#1a0a00);';
                $statusStyle = 'background:rgba(224,90,58,.2);color:#e8836a;border-color:rgba(224,90,58,.3);';
            }
            $label = ucfirst($stato);
        ?>
        <div class="donation-card" data-stato="<?= htmlspecialchars($stato) ?>">
          <div class="donation-card-top" style="<?= $topStyle ?>"><?= $icon ?><span class="donation-status" style="<?= $statusStyle ?>"><?= htmlspecialchars($label) ?></span></div>
          <div class="donation-card-body">
            <h3><?= htmlspecialchars($p['titolo']) ?></h3>
            <p><?= htmlspecialchars($p['obiettivo'] ?? '') ?></p>
            <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <div class="progress-meta"><span><strong>€ <?= number_format($raccolto,0,',','.') ?></strong> raccolti</span><span>di € <?= number_format($budget,0,',','.') ?></span></div>
            <a href="progetti.php?id=<?= $p['id_pd'] ?>" class="btn-solid" style="font-size:.82rem;padding:.5rem 1rem;">💚 Dona ora</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="text-align:center;margin-top:2rem;">
        <a href="progetti.php" class="btn-outline">Vedi tutti i progetti →</a>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       FEED — TikTok style
  ══════════════════════════════════════════ -->
  <section class="feed-section" id="feed">
    <div class="container">
      <div class="feed-header">
        <div>
          <p class="section-eyebrow">✨ Scopri</p>
          <h2 class="section-title">Feed Marino</h2>
          <p class="section-sub">Contenuti dei ricercatori verificati — video, foto, scoperte</p>
        </div>
        <a href="feed.php" class="btn-outline">Vai al feed completo →</a>
      </div>

      <!-- PHP: foreach($feed_items as $f): -->
      <div class="feed-scroll-track">
        <div class="feed-card">
          <div class="feed-card-bg">🦈</div>
          <div class="feed-card-play">▶</div>
          <div class="feed-card-overlay">
            <p class="feed-card-type">📹 Video · 1:24</p>
            <p class="feed-card-title">Squalo bianco nell'Arcipelago Toscano: avvistamento raro</p>
            <p class="feed-card-author">Dr. Rossi M. · ISPRA</p>
          </div>
        </div>
        <div class="feed-card">
          <div class="feed-card-bg" style="background:linear-gradient(135deg,#002820,#005540);">🪸</div>
          <div class="feed-card-overlay">
            <p class="feed-card-type">📸 Foto</p>
            <p class="feed-card-title">Biocenosi corallina a 40m — Baia di Capri</p>
            <p class="feed-card-author">Dr.ssa Bianchi F. · CNR</p>
          </div>
        </div>
        <div class="feed-card">
          <div class="feed-card-bg" style="background:linear-gradient(135deg,#200a20,#401040);">🐙</div>
          <div class="feed-card-play">▶</div>
          <div class="feed-card-overlay">
            <p class="feed-card-type">📹 Video · 0:45</p>
            <p class="feed-card-title">Polpo mimetizzato su fondale sabbioso: tecnica di caccia</p>
            <p class="feed-card-author">Prof. Verdi A. · Univ. Bologna</p>
          </div>
        </div>
        <div class="feed-card">
          <div class="feed-card-bg" style="background:linear-gradient(135deg,#001830,#003060);">🐬</div>
          <div class="feed-card-overlay">
            <p class="feed-card-type">📸 Foto</p>
            <p class="feed-card-title">Pod di delfini in migrazione — Stretto di Messina</p>
            <p class="feed-card-author">Dr.ssa Marini L. · OGS</p>
          </div>
        </div>
        <div class="feed-card">
          <div class="feed-card-bg" style="background:linear-gradient(135deg,#201000,#402000);">🐠</div>
          <div class="feed-card-play">▶</div>
          <div class="feed-card-overlay">
            <p class="feed-card-type">📹 Video · 2:10</p>
            <p class="feed-card-title">Biodiversità della barriera: 200 specie in 10 minuti</p>
            <p class="feed-card-author">Prof. Costa G. · ISPRA</p>
          </div>
        </div>
        <div class="feed-card">
          <div class="feed-card-bg" style="background:linear-gradient(135deg,#000820,#001540);">🌊</div>
          <div class="feed-card-overlay">
            <p class="feed-card-type">📸 Foto</p>
            <p class="feed-card-title">Abisso Calypso: immagini dal punto più profondo del Med</p>
            <p class="feed-card-author">Team ROV · CNR-ISMAR</p>
          </div>
        </div>
        <div class="feed-card">
          <div class="feed-card-bg" style="background:linear-gradient(135deg,#002010,#003020);">🐢</div>
          <div class="feed-card-play">▶</div>
          <div class="feed-card-overlay">
            <p class="feed-card-type">📹 Video · 3:05</p>
            <p class="feed-card-title">Nidificazione della caretta caretta sul litorale calabrese</p>
            <p class="feed-card-author">Dr. Greco P. · WWF Italia</p>
          </div>
        </div>
      </div>
      <!-- PHP: endforeach; -->
    </div>
  </section>
</main>

<!-- ══════════════════════════════════════════
     FOOTER WAVE TRANSITION → SAND
══════════════════════════════════════════ -->
<div class="wave-sep">
  <svg viewBox="0 0 1440 120" preserveAspectRatio="none" style="height:120px;">
    <!-- water to sand transition with multiple wave layers -->
    <path d="M0,40 C180,80 360,0 540,40 C720,80 900,10 1080,40 C1260,70 1380,20 1440,40 L1440,120 L0,120 Z"
          fill="rgba(27,159,212,.2)"/>
    <path d="M0,60 C240,20 480,80 720,50 C960,20 1200,70 1440,50 L1440,120 L0,120 Z"
          fill="rgba(114,215,240,.12)"/>
    <path d="M0,80 C300,50 600,100 900,70 C1100,50 1300,85 1440,80 L1440,120 L0,120 Z"
          fill="#f5ede0"/>
  </svg>
</div>

<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-brand">
        <span class="logo">🌊 NetSea</span>
        <p>Piattaforma dedicata alla ricerca e alla divulgazione sugli ecosistemi marini. Dati aggiornati, specie monitorate, news dalla scienza oceanografica.</p>
        <div style="margin-top:1.25rem;display:flex;gap:.75rem;">
          <a href="#" style="color:#7a6a58;font-size:1.2rem;text-decoration:none;">𝕏</a>
          <a href="#" style="color:#7a6a58;font-size:1.2rem;text-decoration:none;">📷</a>
          <a href="#" style="color:#7a6a58;font-size:1.2rem;text-decoration:none;">▶</a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Esplora</h4>
        <ul>
          <li><a href="specie.php">Specie Marine</a></li>
          <li><a href="luoghi.php">Luoghi & Habitat</a></li>
          <li><a href="news.php">News Scientifiche</a></li>
          <li><a href="progetti.php">Donazioni</a></li>
          <li><a href="feed.php">Feed Scoperte</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Ricerca</h4>
        <ul>
          <li><a href="#">Rilevazioni Ambientali</a></li>
          <li><a href="#">Enti di Ricerca</a></li>
          <li><a href="#">Pubblicazioni</a></li>
          <li><a href="Registrazione.php">Diventa Ricercatore</a></li>
          <li><a href="Login.php">Area Ricercatori</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Info</h4>
        <ul>
          <li><a href="#">Chi siamo</a></li>
          <li><a href="#">Metodologia</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Termini d'uso</a></li>
          <li><a href="#">Contatti</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 NetSea · Tutti i diritti riservati · Progetto scolastico ITIS</p>
      <p style="font-size:.75rem;color:#b09a80;">🐚 &nbsp; 🦀 &nbsp; 🌿</p>
    </div>
  </div>
</footer>

<script>
/* ══════════════════════════════════════════
   CURSOR
══════════════════════════════════════════ */
const cursor = document.getElementById('cursor');
const cursorRing = document.getElementById('cursorRing');
let mx = 0, my = 0, rx = 0, ry = 0;
document.addEventListener('mousemove', e => {
  mx = e.clientX; my = e.clientY;
  cursor.style.left = mx + 'px';
  cursor.style.top = my + 'px';
});
function animRing() {
  rx += (mx - rx) * .12;
  ry += (my - ry) * .12;
  cursorRing.style.left = rx + 'px';
  cursorRing.style.top = ry + 'px';
  requestAnimationFrame(animRing);
}
animRing();

/* ══════════════════════════════════════════
   USER DROPDOWN
══════════════════════════════════════════ */
function toggleDropdown(e) {
  e.stopPropagation();
  const drop = document.getElementById('userDropdown');
  if (!drop) return;
  drop.classList.toggle('open');
}

function closeAllDropdowns() {
  document.querySelectorAll('.user-dropdown').forEach(d => d.classList.remove('open'));
}

document.addEventListener('click', () => closeAllDropdowns());

/* ══════════════════════════════════════════
   NAVBAR SCROLL
══════════════════════════════════════════ */
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
});

/* ══════════════════════════════════════════
   CAROUSEL
══════════════════════════════════════════ */
const track = document.getElementById('carouselTrack');
const dotsContainer = document.getElementById('carouselDots');
const stripThumbs = document.querySelectorAll('.strip-thumb');
const slides = document.querySelectorAll('.slide');
const totalSlides = slides.length;
let currentSlide = 0;
let autoTimer;

// Build dots
slides.forEach((_, i) => {
  const d = document.createElement('button');
  d.className = 'dot' + (i === 0 ? ' active' : '');
  d.onclick = () => moveTo(i);
  dotsContainer.appendChild(d);
});

function moveTo(idx) {
  currentSlide = ((idx % totalSlides) + totalSlides) % totalSlides;
  track.style.transform = `translateX(-${currentSlide * 100}%)`;
  // dots
  document.querySelectorAll('.dot').forEach((d, i) => d.classList.toggle('active', i === currentSlide));
  // strip
  stripThumbs.forEach((t, i) => t.classList.toggle('active', i === currentSlide));
  resetAuto();
}

function resetAuto() {
  clearInterval(autoTimer);
  autoTimer = setInterval(() => moveTo(currentSlide + 1), 6000);
}

// Strip clicks
stripThumbs.forEach(t => t.addEventListener('click', () => moveTo(+t.dataset.idx)));

// Keyboard
document.addEventListener('keydown', e => {
  if (document.getElementById('searchOverlay').classList.contains('active')) return;
  if (e.key === 'ArrowLeft') moveTo(currentSlide - 1);
  if (e.key === 'ArrowRight') moveTo(currentSlide + 1);
});

// Touch swipe
let touchStartX = 0;
track.addEventListener('touchstart', e => touchStartX = e.touches[0].clientX);
track.addEventListener('touchend', e => {
  const dx = e.changedTouches[0].clientX - touchStartX;
  if (Math.abs(dx) > 50) moveTo(currentSlide + (dx < 0 ? 1 : -1));
});

resetAuto();

/* ══════════════════════════════════════════
   SEARCH
══════════════════════════════════════════ */
// ── RICERCA REALE DAL DATABASE ──────────────────────────────────────────
let searchTimer = null;
let lastQuery = '';

const searchInput = document.getElementById('searchInput');
const overlay = document.getElementById('searchOverlay');
const clearBtn = document.getElementById('clearBtn');

searchInput.addEventListener('input', e => {
  const q = e.target.value.trim();
  clearBtn.style.display = q ? 'block' : 'none';
  if (q.length >= 2) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => cercaNelDB(q), 350);
  } else {
    closeSearch();
  }
});

searchInput.addEventListener('keydown', e => {
  if (e.key === 'Escape') { clearSearch(); closeSearch(); }
  if (e.key === 'Enter') {
    const q = searchInput.value.trim();
    if (q.length >= 2) { clearTimeout(searchTimer); cercaNelDB(q); }
  }
});

async function cercaNelDB(query) {
  lastQuery = query;
  overlay.classList.add('active');
  document.getElementById('queryDisplay').textContent = query;
  document.getElementById('searchResults').innerHTML = '<p style="color:var(--muted);padding:2rem 0;">Ricerca in corso…</p>';

  try {
    const res = await fetch('api/cerca.php?q=' + encodeURIComponent(query));
    const dati = await res.json();
    if (query !== lastQuery) return;
    renderRisultati(dati, query);
  } catch(err) {
    document.getElementById('searchResults').innerHTML = '<p style="color:#e8836a;">Errore durante la ricerca.</p>';
  }
}

function renderRisultati(dati, query) {
  const box = document.getElementById('searchResults');
  let html = '';

  // ── SCHEDA SPECIE ─────────────────────────────────────────────────────
  if (dati.specie) {
    const s = dati.specie;
    const stato = (s.stato_conservazione || '').toUpperCase();
    const colori = {CR:'#e8836a',EN:'#e0a060',VU:'#f0c040',NT:'#c8a830',LC:'#2cb89b',DD:'#5d9ab8'};
    const col = colori[stato] || '#5d9ab8';
    const imgHtml = s.immagine
      ? `<img src="${esc(s.immagine)}" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">`
      : '🐟';

    html += `
    <div style="
      background:linear-gradient(135deg,rgba(11,61,94,.6),rgba(7,30,51,.8));
      border:1px solid rgba(114,215,240,.2);border-radius:16px;
      padding:1.5rem;display:grid;grid-template-columns:1fr 150px;
      gap:1.5rem;align-items:center;margin-bottom:1.5rem;
    ">
      <div>
        <span style="display:inline-block;padding:.2rem .75rem;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;border:1px solid ${col}44;background:${col}18;color:${col};margin-bottom:.75rem;">
          📌 ${esc(stato)} — Specie trovata
        </span>
        <h2 style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;color:#e8f6fc;margin-bottom:.2rem;">${esc(s.nome)}</h2>
        <p style="font-style:italic;color:#5d9ab8;font-size:.9rem;margin-bottom:.75rem;">${esc(s.nome_scientifico||'')}</p>
        <p style="color:#c5e4f5;font-size:.88rem;line-height:1.65;margin-bottom:1rem;">${esc((s.descrizione||'').slice(0,200))}${(s.descrizione||'').length>200?'…':''}</p>
        <a href="specie.php?id=${s.id_specie}" style="display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1.25rem;background:#1b9fd4;color:#04111e;border-radius:8px;text-decoration:none;font-weight:600;font-size:.875rem;">
          Scheda completa →
        </a>
      </div>
      <div style="width:150px;height:150px;border-radius:12px;overflow:hidden;background:linear-gradient(135deg,#0b3d5e,#071e33);border:1px solid rgba(114,215,240,.15);display:flex;align-items:center;justify-content:center;font-size:5rem;flex-shrink:0;">
        ${imgHtml}
      </div>
    </div>`;
  }

  // ── ALTRE SPECIE ──────────────────────────────────────────────────────
  const altreSpecie = (dati.specie_lista||[]).filter(s => !dati.specie || s.id_specie != dati.specie.id_specie);
  if (altreSpecie.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">🐠 Altre specie correlate</p>`;
    altreSpecie.forEach(s => {
      const stato = (s.stato_conservazione||'').toUpperCase();
      const colori = {CR:'#e8836a',EN:'#e0a060',VU:'#f0c040',LC:'#2cb89b'};
      const col = colori[stato]||'#5d9ab8';
      html += `
      <a href="specie.php?id=${s.id_specie}" style="display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.25);border:1px solid rgba(114,215,240,.08);border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.6rem;text-decoration:none;transition:border-color .2s;" onmouseover="this.style.borderColor='rgba(114,215,240,.25)'" onmouseout="this.style.borderColor='rgba(114,215,240,.08)'">
        <span style="font-size:1.8rem;">🐟</span>
        <div style="flex:1;">
          <p style="color:#e8f6fc;font-size:.9rem;font-weight:500;">${esc(s.nome)}</p>
          <p style="color:#5d9ab8;font-size:.78rem;font-style:italic;">${esc(s.nome_scientifico||'')}</p>
        </div>
        <span style="padding:.2rem .65rem;border-radius:20px;font-size:.68rem;font-weight:700;border:1px solid ${col}44;background:${col}18;color:${col};">${esc(stato)}</span>
      </a>`;
    });
    html += '<div style="height:1rem;"></div>';
  }

  // ── NEWS ──────────────────────────────────────────────────────────────
  if (dati.news && dati.news.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">📰 News correlate</p>`;
    dati.news.forEach(n => {
      html += `
      <a href="news_detail.php?id=${n.id_news}" style="display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.07);border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.6rem;text-decoration:none;" onmouseover="this.style.borderColor='rgba(114,215,240,.2)'" onmouseout="this.style.borderColor='rgba(114,215,240,.07)'">
        <span style="font-size:1.6rem;">📄</span>
        <div style="flex:1;">
          <p style="color:#e8f6fc;font-size:.88rem;font-weight:500;">${esc(n.titolo)}</p>
          <p style="color:#5d9ab8;font-size:.75rem;">${esc((n.nome_autore||'')+ ' '+(n.cognome_autore||''))}</p>
        </div>
        <p style="color:#5d9ab8;font-size:.74rem;flex-shrink:0;">${formatDate(n.data_pub)}</p>
      </a>`;
    });
    html += '<div style="height:1rem;"></div>';
  }

  // ── MEDIA ─────────────────────────────────────────────────────────────
  if (dati.media && dati.media.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">🎬 Foto & Video</p>
    <div style="display:flex;flex-wrap:wrap;gap:.65rem;margin-bottom:1.5rem;">`;
    dati.media.forEach(m => {
      html += `
      <a href="${esc(m.url||'#')}" target="_blank" style="flex:0 0 calc(33.33% - .45rem);min-width:150px;background:rgba(11,61,94,.3);border:1px solid rgba(114,215,240,.1);border-radius:10px;padding:.85rem;text-decoration:none;">
        <p style="font-size:.68rem;color:#1b9fd4;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.3rem;">${m.tipo==='video'?'📹 Video':'📸 Foto'}</p>
        <p style="color:#e8f6fc;font-size:.83rem;font-weight:500;">${esc(m.titolo)}</p>
      </a>`;
    });
    html += '</div>';
  }

  // ── DONAZIONI ─────────────────────────────────────────────────────────
  if (dati.donazioni && dati.donazioni.length) {
    html += `<p style="font-size:.75rem;letter-spacing:.1em;text-transform:uppercase;color:#5d9ab8;margin-bottom:.75rem;">💚 Progetti correlati</p>`;
    dati.donazioni.forEach(d => {
      html += `
      <a href="progetti.php?id=${d.id_pd}" style="display:flex;align-items:center;gap:1rem;background:rgba(11,61,94,.2);border:1px solid rgba(114,215,240,.07);border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.6rem;text-decoration:none;" onmouseover="this.style.borderColor='rgba(44,184,155,.25)'" onmouseout="this.style.borderColor='rgba(114,215,240,.07)'">
        <span style="font-size:1.6rem;">🌿</span>
        <div>
          <p style="color:#e8f6fc;font-size:.88rem;font-weight:500;">${esc(d.titolo)}</p>
          <p style="color:#5d9ab8;font-size:.75rem;">${esc((d.obiettivo||'').slice(0,80))}</p>
        </div>
      </a>`;
    });
  }

  // ── NESSUN RISULTATO ──────────────────────────────────────────────────
  if (!html) {
    html = `<div style="text-align:center;padding:3rem;color:#5d9ab8;">
      <div style="font-size:3rem;margin-bottom:1rem;">🌊</div>
      <p>Nessun risultato per "<strong style="color:#72d7f0;">${esc(query)}</strong>"</p>
    </div>`;
  }

  box.innerHTML = html;
}

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatDate(d) {
  if (!d) return '';
  try { return new Date(d).toLocaleDateString('it-IT',{day:'2-digit',month:'short',year:'numeric'}); } catch(e){return d;}
}
function sortResults() {}





/* ══════════════════════════════════════════
   DONATION FILTERS
══════════════════════════════════════════ */
function filterDonations(filter, btn) {
  document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.donation-card').forEach(card => {
    const stato = card.dataset.stato;
    card.style.display = (filter === 'all' || stato === filter) ? 'block' : 'none';
  });
}

/* ══════════════════════════════════════════
   SCROLL ANIMATIONS
══════════════════════════════════════════ */
const observer = new IntersectionObserver(entries => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: .15 });
document.querySelectorAll('.anim').forEach(el => observer.observe(el));

/* ══════════════════════════════════════════
   PROGRESS BARS ANIMATE ON VISIBLE
══════════════════════════════════════════ */
const progObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.style.width = e.target.dataset.width || e.target.style.width;
    }
  });
}, { threshold: .2 });
document.querySelectorAll('.progress-fill').forEach(el => progObserver.observe(el));
</script>
</body>
</html>