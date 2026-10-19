<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cursos') · SIGECAP</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f5f7;color:#253247;font:16px/1.6 system-ui,sans-serif}
        header{background:#183a4a;color:#fff;padding:18px 24px}header a{color:inherit;text-decoration:none;font-weight:700}
        main{max-width:920px;margin:32px auto;padding:0 18px}h1,h2,h3{line-height:1.25}h1{font-size:2rem}h2{font-size:1.35rem}
        .card{background:white;border:1px solid #dbe1e7;border-radius:12px;padding:24px;margin-bottom:20px}
        .muted{color:#586579}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        label{display:block;font-weight:600}input,select{width:100%;padding:11px;margin-top:5px;border:1px solid #a8b4c0;border-radius:6px;font:inherit;background:#fff}
        button,.button{display:inline-block;background:#176451;color:white;border:0;border-radius:6px;padding:11px 18px;font:inherit;cursor:pointer;text-decoration:none}
        a{color:#176451}.notice{background:#eef4f8;padding:14px;border-radius:6px}.error{background:#fff0ee;color:#8d2525}.text{white-space:pre-line}
        .qr img{width:280px;max-width:100%;height:auto}.folio{font-size:1.5rem;font-weight:700;overflow-wrap:anywhere}
        :focus-visible{outline:3px solid #d99021;outline-offset:3px}footer{padding:24px;text-align:center;color:#586579}
        @media(max-width:600px){.grid{grid-template-columns:1fr}.card{padding:18px}main{margin-top:20px}}
    </style>
</head>
<body>
<header><a href="/cursos">SIGECAP · Capacitación</a></header>
<main>@yield('content')</main>
<footer>Registro de cursos y actividades de capacitación</footer>
</body>
</html>
