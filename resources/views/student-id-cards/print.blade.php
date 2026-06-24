<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Cards</title>
    <style>
        @page {
            size: 55mm 87mm;
            margin: 0;
        }

        :root {
            --card-width: 5.5cm;
            --card-height: 8.7cm;
            --paper: #ffffff;
            --page: #f3f7fb;
            --ink: #17212d;
            --muted: #4f5b6a;
        }

        * { box-sizing: border-box; }

        html, body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            forced-color-adjust: none;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        }

        .toolbar {
            max-width: 1180px;
            margin: 24px auto 0;
            padding: 0 16px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .toolbar a,
        .toolbar button {
            border: 1px solid #d7e1ea;
            background: var(--paper);
            color: var(--ink);
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar button {
            background: #0f4c81;
            border-color: #0f4c81;
            color: #fff;
        }

        .page {
            max-width: 1180px;
            margin: 16px auto 40px;
            /* padding: 0 16px; */
        }

        .cards {
            display: grid;
            gap: 20px;
            justify-content: center;
            grid-template-columns: repeat(auto-fit, minmax(var(--card-width), var(--card-width)));
        }

        .card {
            width: var(--card-width);
            min-height: var(--card-height);
            background: linear-gradient(180deg, #ffffff 0%, #fffefb 83.5%, var(--footer-top) 83.5%, var(--footer-bottom) 100%);
            /* border: 1px solid #dce5ed; */
            /* border-radius: 20px; */
            overflow: hidden;
            box-shadow: 0 16px 36px rgba(16, 32, 51, 0.08);
            page-break-inside: avoid;
            position: relative;
        }

        .card::before {
            content: "";
            position: absolute;
            left: -14%;
            right: -14%;
            top: 1.15cm;
            height: 0.44cm;
            background: linear-gradient(90deg, var(--accent-dark) 0%, var(--accent-light) 55%, var(--accent-dark) 100%);
            box-shadow: 0 0.04cm 0.12cm rgba(189, 139, 0, 0.22);
        }

        .card-inner {
            position: relative;
            z-index: 1;
            min-height: var(--card-height);
            padding: 0.25cm; /*0.14cm 0.24cm 0*/
            display: flex;
            flex-direction: column;
            
        }

        .brand {
            text-align: center;
        }

        .brand-logo {
            height: 0.62cm;
            object-fit: cover;
            max-width: 100%;
        }

        .brand-mark {
            font-size: 0.66cm;
            font-weight: 900;
            letter-spacing: 0.03em;
            color: var(--brand-color);
            line-height: 0.9;
        }

        .brand-name {
            font-size: 0.17cm;
            letter-spacing: 0.09em;
            font-weight: 800;
            color: var(--brand-color);
            text-transform: uppercase;
            line-height: 1;
        }

        .photo-wrap {
            margin: 0.64cm auto 0;
            width: 1.48cm;
        }

        .photo-frame {
            width: 1.48cm;
            height: 1.48cm;
            border-radius: 0.2cm;
            padding: 0.07cm;
            background: linear-gradient(180deg, var(--accent-light) 0%, var(--accent) 100%);
            box-shadow: 0 0.04cm 0.12cm rgba(164, 121, 0, 0.28);
        }

        .photo {
            width: 100%;
            height: 100%;
            border-radius: 0.16cm;
            background: #313543;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.42cm;
            font-weight: 800;
            letter-spacing: 0.03em;
            overflow: hidden;
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .name-block {
            margin-top: 0.12cm;
            text-align: center;
        }

        .name {
            font-size: 0.25cm;
            font-weight: 900;
            color: var(--ink);
            line-height: 1.08;
        }

        .secondary-name {
            margin-top: 0.025cm;
            font-size: 0.13cm;
            color: var(--muted);
            line-height: 1.05;
        }

        .secondary-name:empty {
            display: none;
        }

        .data {
            margin-top: 0.1cm;
            display: grid;
            gap: 0.035cm;
            justify-items: center;
        }

        .data-row {
            display: grid;
            grid-template-columns: 1.55cm 0.08cm 1fr;
            gap: 0.4cm;
            width: 100%;
            max-width: 3.95cm;
            font-size: 0.23cm;
            line-height: 1.5;
            color: #101820;
            text-align: left;
        }

        .data-row .label,
        .data-row .colon,
        .data-row .value {
            font-weight: 700;
        }

        .data-row .label {
            white-space: nowrap;
            text-align: left;
        }

        .barcode-wrap {
            margin-top: auto;
            padding-top: 0.08cm;
        }

        .barcode-box {
            width: 100%;
            height: 2.1cm;
            border-radius: 0.03cm;
            background: #ffffff;
            border: 0.02cm solid #d6d6d6;
            padding: 0.02cm 0.025cm;
            display: flex;
            align-items: stretch;
            justify-content: stretch;
        }

        .barcode-box svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .barcode-text {
            margin-top: 0.03cm;
            text-align: center;
            font-size: 0.16cm;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #222;
        }

        .footer-strip {
            margin-top: 0.03cm;
            background: var(--footer-bottom);
            color: #16140e;
            text-align: center;
            padding: 0.04cm 0.1cm 0.045cm;
            font-size: 0.108cm;
            font-weight: 800;
            line-height: 1.08;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { max-width: none; margin: 0; padding: 0; }
            .cards { gap: 0.35cm; }
            .card {
                box-shadow: none;
                /* border-radius: 14px; */
                /* border-color: #cfd9e3; */
                min-height: var(--card-height);
                height: var(--card-height);
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('student-id-cards.index') }}">Back to ID Cards</a>
        <button type="button" onclick="window.print()">Print ID Cards</button>
    </div>

    <div class="page">
        <div class="cards">
            @foreach ($cards as $card)
                <section class="card" style="--accent: {{ $card['theme']['accent'] }}; --accent-dark: {{ $card['theme']['accent_dark'] }}; --accent-light: {{ $card['theme']['accent_light'] }}; --footer-top: {{ $card['theme']['footer_top'] }}; --footer-bottom: {{ $card['theme']['footer_bottom'] }}; --brand-color: {{ $card['theme']['brand'] }};">
                    <div class="card-inner">
                        <div class="brand">
                            @if ($cardSettings['school_logo_data_url'])
                                <img src="{{ $cardSettings['school_logo_data_url'] }}" alt="School Logo" class="brand-logo">
                            @else
                                <div class="brand-mark">{{ strtoupper(substr($cardSettings['school_name'], 0, 4)) }}</div>
                            @endif
                            <div class="brand-name">{{ $cardSettings['school_name'] }}</div>
                        </div>

                        <div class="photo-wrap">
                            <div class="photo-frame">
                                <div class="photo">
                                    @if (!empty($card['photo_data_url']))
                                        <img src="{{ $card['photo_data_url'] }}" alt="{{ $card['display_name'] }}">
                                    @else
                                        {{ $card['avatar_text'] }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="name-block">
                            <div class="name">{{ $card['display_name'] }}</div>
                            <div class="secondary-name">{{ $card['secondary_name'] }}</div>
                        </div>

                        <div class="data">
                            @foreach ($card['details'] as $detail)
                                <div class="data-row">
                                    <div class="label">{{ $detail['label'] }}</div>
                                    <div class="colon">:</div>
                                    <div class="value">{{ $detail['value'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="barcode-wrap">
                            <div class="barcode-box" aria-hidden="true">
                                {!! $card['barcode_svg'] !!}
                            </div>
                            <div class="barcode-text">{{ $card['barcode_value'] }}</div>
                        </div>

                        @if ($cardSettings['school_phone'] || $cardSettings['school_email'])
                            <div class="footer-strip">
                                @if ($cardSettings['school_phone'] || $cardSettings['school_email'])
                                    <div>
                                        {{ collect([$cardSettings['school_phone'], $cardSettings['school_email']])->filter()->implode('  |  ') }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</body>
</html>
