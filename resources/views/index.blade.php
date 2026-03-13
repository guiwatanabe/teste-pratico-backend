<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hello World</title>
    <style>
        :root {
            --ink: #1c1a19;
            --paper: #f6f1e6;
            --accent: #2f5d62;
            --accent-soft: #87a9a6;
            --shadow: rgba(0, 0, 0, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            color: var(--ink);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(60% 80% at 80% 20%, rgba(47, 93, 98, 0.25), transparent 60%),
                radial-gradient(70% 90% at 20% 80%, rgba(135, 169, 166, 0.3), transparent 60%),
                var(--paper);
        }

        .card {
            width: min(420px, 90vw);
            padding: 28px 32px;
            background: #fffaf2;
            border: 1px solid rgba(47, 93, 98, 0.2);
            border-radius: 18px;
            box-shadow: 0 18px 40px var(--shadow);
            transform: translateY(10px);
            opacity: 0;
            animation: rise-in 700ms ease-out forwards;
        }

        .repo-row {
            display: flex;
            gap: 1em;
            justify-content: center;
            align-items: center;
        }

        .repo-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
        }

        .repo-title {
            margin: 0;
            font-size: 1.1rem;
            line-height: 1.2;
        }

        a {
            color: var(--accent);
            text-decoration: none;
        }

        @keyframes rise-in {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <main class="card" role="main">
        <div class="repo-row">
            <svg aria-hidden="true" focusable="false" class="octicon octicon-mark-github" viewBox="0 0 24 24"
                width="32" height="32" fill="currentColor" display="inline-block" overflow="visible"
                style="vertical-align:text-bottom">
                <path
                    d="M12 1C5.923 1 1 5.923 1 12c0 4.867 3.149 8.979 7.521 10.436.55.096.756-.233.756-.522 0-.262-.013-1.128-.013-2.049-2.764.509-3.479-.674-3.699-1.292-.124-.317-.66-1.293-1.127-1.554-.385-.207-.936-.715-.014-.729.866-.014 1.485.797 1.691 1.128.99 1.663 2.571 1.196 3.204.907.096-.715.385-1.196.701-1.471-2.448-.275-5.005-1.224-5.005-5.432 0-1.196.426-2.186 1.128-2.956-.111-.275-.496-1.402.11-2.915 0 0 .921-.288 3.024 1.128a10.193 10.193 0 0 1 2.75-.371c.936 0 1.871.123 2.75.371 2.104-1.43 3.025-1.128 3.025-1.128.605 1.513.221 2.64.111 2.915.701.77 1.127 1.747 1.127 2.956 0 4.222-2.571 5.157-5.019 5.432.399.344.743 1.004.743 2.035 0 1.471-.014 2.654-.014 3.025 0 .289.206.632.756.522C19.851 20.979 23 16.854 23 12c0-6.077-4.922-11-11-11Z">
                </path>
            </svg>
            <div>
                <a href="https://github.com/guiwatanabe" class="repo-link" target="_blank" rel="noopener">
                    <h3 class="repo-title">
                        guiwatanabe
                    </h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5" />
                        <path fill-rule="evenodd"
                            d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z" />
                    </svg>
                </a>

            </div>
        </div>
    </main>
</body>

</html>
