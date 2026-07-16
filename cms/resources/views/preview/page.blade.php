<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Prévisualisation — {{ $translation?->title ?? 'Page sans titre' }}</title>
    <style>
        :root{font-family:Inter,system-ui,sans-serif;color:#18181b;background:#faf7f2}*{box-sizing:border-box}body{margin:0}.bar{position:sticky;top:0;z-index:5;background:#18181b;color:white;padding:12px 5vw;display:flex;justify-content:space-between;gap:20px}.bar strong{color:#f43f7a}.page{width:min(1050px,90vw);margin:48px auto}.hero{padding:64px;border-radius:24px;background:white;box-shadow:0 12px 45px #18181b12}.hero h1{font-family:Georgia,serif;font-size:clamp(2.3rem,6vw,5rem);margin:.2em 0}.block{margin:28px 0;padding:28px;border-radius:18px;background:white;border:1px solid #e7ded2}.block header{display:flex;justify-content:space-between;gap:20px;color:#be185d}.block pre{white-space:pre-wrap;word-break:break-word;background:#fafafa;padding:18px;border-radius:12px;overflow:auto}@media(max-width:640px){.hero{padding:30px}.page{margin-top:24px}.bar{font-size:.85rem}}
    </style>
</head>
<body>
<div class="bar"><span><strong>PRÉVISUALISATION</strong> — non publiée</span><span>{{ $page->status->value }} · version {{ $page->lock_version }}</span></div>
<main class="page">
    <section class="hero">
        <small>{{ $translation?->title }}</small>
        <h1>{{ $translation?->h1 }}</h1>
        @if($translation?->intro)<p>{{ $translation->intro }}</p>@endif
    </section>
    @foreach($page->blocks as $block)
        @php($content = $block->translations->firstWhere('locale', config('cms.default_locale'))?->content ?? [])
        <article class="block" @if(!$block->is_visible) style="opacity:.55" @endif>
            <header><strong>{{ $block->type->value }}</strong><span>{{ $block->is_visible ? 'Visible' : 'Masqué' }}</span></header>
            <pre>{{ json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </article>
    @endforeach
</main>
</body>
</html>
