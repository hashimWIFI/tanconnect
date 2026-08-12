<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIT Africa Solutions Limited | Telecommunication, ICT & Managed Services Experts</title>
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --dark: #0f172a;
            --light: #f8fafc;
            --text-dark: #334155;
            --text-light: #94a3b8;
            --border: #e2e8f0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            background-color: var(--light);
            line-height: 1.6;
        }

        /* Fixed Navigation Header */
        header {
            background-color: var(--dark);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
        }

        .logo-box h1 {
            color: #ffffff;
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .logo-box span {
            color: var(--primary);
            font-size: 11px;
            display: block;
        }

        .right-header {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        nav a {
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            margin-left: 15px;
            font-weight: 500;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary);
        }

        /* Language Switcher Button Controls */
        .lang-switcher {
            display: flex;
            background: #1e293b;
            padding: 3px;
            border-radius: 6px;
            border: 1px solid #334155;
        }

        .lang-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 11px;
            font-weight: bold;
            padding: 4px 10px;
            cursor: pointer;
            border-radius: 4px;
            text-transform: uppercase;
            transition: all 0.2s;
        }

        .lang-btn.active {
            background-color: var(--primary);
            color: #ffffff;
        }

        /* Language Visibility Masking Engine */
        .lang-en, .lang-sw { display: none; }
        
        body.mode-en .lang-en { display: block; }
        body.mode-en .inline-en { display: inline; }
        body.mode-en .inline-sw { display: none; }
        
        body.mode-sw .lang-sw { display: block; }
        body.mode-sw .inline-sw { display: inline; }
        body.mode-sw .inline-en { display: none; }

        /* Hero Brand Banner */
        .hero {
            background: linear-gradient(135deg, var(--dark) 70%, var(--primary-dark));
            color: #ffffff;
            padding: 150px 20px 80px 20px;
            text-align: center;
        }

        .hero h2 {
            font-size: 32px;
            margin: 0 auto 15px auto;
            max-width: 900px;
            color: #ffffff;
        }

        .hero p {
            color: var(--text-light);
            font-size: 15px;
            max-width: 700px;
            margin: 0 auto 25px auto;
        }

        .hero-badge {
            background-color: rgba(2, 132, 199, 0.2);
            color: var(--primary);
            border: 1px solid var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Dynamic Layout Sections */
        section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h3 {
            font-size: 24px;
            color: var(--dark);
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-title div {
            width: 60px;
            height: 4px;
            background-color: var(--primary);
            margin: 0 auto;
            border-radius: 2px;
        }

        /* Flexible Components Grid Maps */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 30px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        .card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .card h4 {
            margin: 0 0 15px 0;
            color: var(--dark);
            font-size: 16px;
            text-transform: uppercase;
            border-bottom: 2px solid var(--light);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card p {
            font-size: 13px;
            margin: 0 0 15px 0;
            color: #475569;
            text-align: justify;
        }

        .card ul {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            color: var(--text-dark);
        }

        .card ul li {
            margin-bottom: 8px;
        }

        /* Callout / Highlight Blocks */
        .highlight-box {
            background-color: var(--dark);
            color: #ffffff;
            border-radius: 12px;
            padding: 40px;
            margin-top: 35px;
            border-left: 5px solid var(--primary);
        }

        .highlight-box h4 {
            color: var(--primary);
            margin: 0 0 15px 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .highlight-box p {
            font-size: 14px;
            color: var(--text-light);
            margin: 0 0 20px 0;
            text-align: justify;
        }

        .badge-strip {
            font-size: 11px;
            color: #38bdf8;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #1e293b;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        /* Contact Details Panel layout */
        .contact-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px;
        }

        .contact-info {
            flex: 1;
            min-width: 280px;
        }

        .contact-info h4 {
            margin: 0 0 20px 0;
            color: var(--dark);
            text-transform: uppercase;
            font-size: 15px;
        }

        .contact-detail-row {
            margin-bottom: 20px;
            font-size: 13px;
        }

        .contact-detail-row b {
            color: var(--dark);
            display: block;
            margin-bottom: 4px;
        }

        /* Footer Frame */
        footer {
            background-color: var(--dark);
            color: var(--text-light);
            text-align: center;
            padding: 40px 20px;
            font-size: 12px;
            border-top: 1px solid #1e293b;
        }

        footer b {
            color: #ffffff;
        }

        @media (max-width: 992px) {
            nav { display: none; }
            .grid-2 { grid-template-columns: 1fr; }
            .hero h2 { font-size: 26px; }
        }
    </style>
</head>
<body class="mode-en">

    <!-- 📌 FIXED NAVIGATION HEADER -->
    <header>
        <div class="nav-container">
            <div class="logo-box">
                <h1>NIT AFRICA SOLUTIONS</h1>
                <span>Networking Your Business With Future...</span>
            </div>
            <div class="right-header">
                <nav>
                    <a href="#about"><span class="inline-en">About</span><span class="inline-sw">Kuhusu Sisi</span></a>
                    <a href="#services"><span class="inline-en">Services</span><span class="inline-sw">Huduma Kuu</span></a>
                    <a href="#group"><span class="inline-en">Solutions</span><span class="inline-sw">Mifumo</span></a>
                    <a href="#mss"><span class="inline-en">Support</span><span class="inline-sw">Usimamizi</span></a>
                    <a href="#contact"><span class="inline-en">Contact</span><span class="inline-sw">Mawasiliano</span></a>
                </nav>
                <div class="lang-switcher">
                    <button class="lang-btn active" id="btn-en" onclick="setLanguage('en')">EN</button>
                    <button class="lang-btn" id="btn-sw" onclick="setLanguage('sw')">SW</button>
                </div>
            </div>
        </div>
    </header>

    <!-- 📌 HERO BRAND ACCELERATOR BANNER -->
    <div class="hero">
        <span class="hero-badge"><span class="inline-en">Company Profile</span><span class="inline-sw">Wasifu wa Kampuni</span></span>
        
        <h2 class="lang-en">Dealers in Installation, Maintenance & Sales of Optic Fiber Cables & Telecommunication Networks</h2>
        <h2 class="lang-sw">Wataalamu wa Ufungaji, Matengenezo na Mauzo ya Nyaya za Fiber Optic na Mitandao ya Mawasiliano</h2>

    <section id="group">
        <div class="section-title">
            <h3><span class="inline-en">Technology Solution Group</span><span class="inline-sw">Mifumo Maalumu ya Kiteknolojia</span></h3>
            <div></div>
        </div>
        
        <p class="lang-en" style="font-size: 14px; text-align: center; max-width: 900px; margin: -20px auto 4px auto; color: #475569;">
            Our focus is on <b>Solution Integration</b> rather than Systems Integration. We partner with <b>Cisco, D-Link, Siemon, HP</b>, and other world-class leaders to deliver optimized layouts. Building a network solution involves an infrastructure assessment, understanding unique requirements, designing an optimal architecture, and seamless deployment to reduce IT costs, improve efficiency, and rationalize workloads.
        </p>
        <p class="lang-sw" style="font-size: 14px; text-align: center; max-width: 900px; margin: -20px auto 4px auto; color: #475569;">
            Lengo letu kuu ni kwenye <b>Ujumuishaji wa Suluhisho (Solution Integration)</b> badala ya ujumuishaji wa mifumo ya kawaida. Tunashirikiana na makampuni makubwa duniani kama <b>Cisco, D-Link, Siemon, HP</b>, na wengine kutoa huduma bora. Kazi yetu inahusisha kufanya tathmini ya miundombinu ya sasa, kuelewa mahitaji ya kipekee ya mteja, kusanifu mtandao bora, na hatimaye kuufunga ili kupunguza gharama za IT, kuongeza ufanisi na kurahisisha kazi.
        </p>

        <div class="grid-3" style="margin-top: 40px;">
            <!-- Card 1: Internetworking & Cabling -->
            <div class="card">
                <h4>🌐 Internetworking & LAN/WAN</h4>
                <p class="lang-en">Designing robust structures to manage converged routing and workload distributions safely across multiple zones:</p>
                <p class="lang-sw">Usanifu wa miundombinu thabiti kusimamia uelekezaji wa mitandao na ugawaji wa mzigo wa kazi kwa usalama:</p>
                <ul>
                    <li>Structured Cabling Solutions</li>
                    <li>Converged Local Area Networking (LAN)</li>
                    <li>Converged Wide Area Networking (WAN)</li>
                    <li>High-speed enterprise Switching infrastructures</li>
                    <li>Virtual Private Networks (VPN) deployment</li>
                </ul>
            </div>

            <!-- Card 2: Unified Communications -->
            <div class="card">
                <h4>📞 Unified Communications</h4>
                <p class="lang-en">IP Telephony is not about reducing call charges but evolving the way business is conducted by improving communication reliability, flexibility, and mobility:</p>
                <p class="lang-sw">Mifumo ya Simu ya IP (IP Telephony) si tu kuhusu kupunguza gharama za simu, bali kuboresha njia ambayo biashara inaendeshwa kwa kuongeza uaminifu na kubadilika:</p>
                <ul>
                    <li>Computer Telephony Integration (CTI)</li>
                    <li>Interactive Voice Response (IVR) setups</li>
                    <li>Unified Messaging frameworks</li>
                    <li>Enhanced multimedia web collaboration</li>
                    <li>Voice over IP (VoIP) enterprise mapping</li>
                </ul>
            </div>

            <!-- Card 3: Wireless Solutions -->
            <div class="card">
                <h4>📶 Wireless Solutions</h4>
                <p class="lang-en">Wireless infrastructure enables a higher degree of mobility and uninterrupted network access—allowing your enterprise to stay connected safely on the move:</p>
                <p class="lang-sw">Miundombinu ya mtandao usio na waya (Wireless) inaleta uhuru mkubwa wa kutembea na ufikiaji wa mtandao bila kukatika—kuwa hewani wakati wowote:</p>
                <ul>
                    <li>Enterprise unwired connectivity migration</li>
                    <li>Anywhere, anytime real-time data access</li>
                    <li>Easy corporate environment deployments</li>
                    <li>Hassle-free setups without entire office re-wiring</li>
                    <li>High-capacity managed Wi-Fi architectures</li>
                </ul>
            </div>
        </div>

        <!-- CONVERGENCE SEGMENT EXPLANATION -->
        <div class="highlight-box">
            <div class="lang-en">
                <h4>The Paradigm of Convergence Practice</h4>
                <p>Technology today enables the convergence of data, voice, and video into a single network enabled by IP, as opposed to traditional networks which carry data, voice, and video separately. NIT Africa Solutions Limited has considerable expertise and experience in convergence practice to help deliver unique solutions to our customers. Key benefits of a converged network include significantly reduced voice and data operational costs, simplified hardware networking layouts, improved staff productivity, enhanced mobility at work, and a dramatically better customer experience.</p>
                <span class="badge-strip">IP Technology Focus: Routing | Switching | VoIP | Streaming Convergence</span>
            </div>
            <div class="lang-sw">
                <h4>Umuhimu wa Mifumo Iliyounganishwa (Convergence Practice)</h4>
                <p>Teknolojia ya leo inaruhusu kuunganishwa kwa data, sauti, na video kwenye mtandao mmoja unaoendeshwa na mfumo wa IP, tofauti na mitandao ya kizamani ambayo ilisafirisha huduma hizi kando kando. NIT Africa Solutions Limited ina utalaamu na uzoefu mkubwa katika uunganishaji huu ili kuleta suluhisho la kipekee kwa wateja wetu. Faida kuu za mtandao uliounganishwa ni pamoja na kupunguza kwa kiasi kijubwa gharama za uendeshaji wa sauti na data, kurahisisha muundo mzima vya mitandao ya vifaa, kuongeza tija ya wafanyakazi, na kuleta uzoefu bora zaidi kwa wateja wako.</p>
                <span class="badge-strip">Mifumo ya IP: Internetworking | Maongezi ya Sauti | Video | Data Converged</span>
            </div>
        </div>

        <div class="grid-2" style="margin-top: 30px;">
            <!-- Security Deep Dive -->
            <div class="card" style="border-top: 3px solid #ef4444;">
                <h4>🔒 Comprehensive Enterprise Security</h4>
                <p class="lang-en">Unauthorized access, Denial of Service (DoS), confidentiality breaches, data destruction—these are critical threats faced by organizations worldwide. As technology advances, threats escalate, making security of paramount importance. It requires best-in-class technology and specialist engineers to assess, design, develop, and implement a framework that safeguards your information assets and creates a risk-free environment.</p>
                <p class="lang-sw">Ufikiaji usioidhinishwa wa mifumo, mashambulizi ya mitandao (DoS), uvujaji wa siri za kampuni, na uharibifu wa data—haya ni baadhi ya matatizo yanayokabili mashirika duniani kote. Teknolojia inapoendelea, ndivyo vitisho navyo vinavyoongezeka. Inahitaji teknolojia bora na wahandisi waliobobea kufanya tathmini, usanifu, na ufungaji wa mfumo wa usalama utakaolinda rasilimali za taarifa za kampuni yako.</p>
                <ul class="lang-en">
                    <li>Securing corporate networks with industrial firewalls</li>
                    <li>IT Security Policy framework defining and monitoring</li>
                    <li>Protecting from external invaders with advanced antivirus</li>
                    <li>Comprehensive networks and systems infrastructure auditing</li>
                    <li>Detailed security vulnerability reporting and remediation</li>
                </ul>
                <ul class="lang-sw">
                    <li>Kulinda mitandao ya kampuni kwa kutumia Firewalls za kisasa vya viwandani</li>
                    <li>Uwekaji na usimamiaji wa miongozo na sera za usalama wa mifumo (IT Policy)</li>
                    <li>Ulinzi dhidi ya wavamizi wa nje kwa kutumia Antivirus za hali ya juu</li>
                    <li>Ukaguzi kamili vya miundombinu ya mitandao na mifumo ya kompyuta</li>
                    <li>Utoaji wa ripoti za kina za udhaifu wa usalama na kuzifanyia matengenezo</li>
                </ul>
            </div>

            <!-- Enterprise Management Deep Dive -->
            <div class="card" style="border-top: 3px solid var(--primary);">
                <h4>📊 IT Infrastructure Enterprise Management</h4>
                <p class="lang-en">Organizations face the continuous challenge of managing the complexity of growing IT infrastructures while controlling costs. NIT Africa Solutions Limited brings new perspectives to management that alleviate complexity. Our solutions are deployed to ensure monitoring and management of key operational metrics such as real-time system availability, fault tolerance, and baseline performance.</p>
                <p class="lang-sw">Mashirika ya sasa yanakabiliwa na changamoto ya kusimamia utata wa miundombinu ya IT inayokua huku wakijaribu kupunguza gharama. NIT Africa Solutions Limited inaleta mtazamo mpya katika usimamizi wa miundombinu ya IT unaoondoa utata huo. Suluhisho zetu hutekelezwa ili kuhakikisha ufuatiliaji na usimamizi wa vipimo muhimu kama vile upatikanaji wa mifumo na utendaji kazi kwa wakati halisi.</p>
                <ul class="lang-en">
                    <li>Desktop & end-point client management</li>
                    <li>Core network hardware and layout management</li>
                    <li>Systems and application performance monitoring</li>
                    <li>Data center environment management</li>
                    <li>Backup and storage configuration management (DAS, NAS, SAN Solution)</li>
                    <li>Help Desk infrastructure & service asset management</li>
                    <li>Advanced Clustering, Extended Clusters, & Fault Tolerance</li>
                    <li>Disaster Recovery planning & real-time replication loops</li>
                </ul>
                <ul class="lang-sw">
                    <li>Usimamizi wa kompyuta za watumiaji (Desktop & End-point management)
                    <li>Usimamizi vya vifaa kuu vya mtandao na mpangilio wakeUfuatiliaji wa utendaji kazi wa mifumo na programu za kompyuta
                    <li>Usimamizi wa vituo vikuu vya data (Data Center management)
                    <li>Usimamizi wa chelezo na uhifadhi wa data (DAS, NAS, na SAN Solutions)
                    <li>Miundombinu ya Dawati la Msaada (Help Desk) na usimamizi wa rasilimali
                    <li>Mifumo ya juu ya uunganishaji wa seva (Clustering & Extended Clusters)
                    <li>Mipango ya kurejesha mifumo baada ya majanga (Disaster Recovery & Real-time replication)

© 2026 NIT Africa Solutions Limited. All Rights Reserved. TANConnect is a registered trade brand of NIT Africa Solutions Limited.

© 2026 NIT Africa Solutions Limited. Haki zote zimehifadhiwa. TANConnect ni chapa iliyosajiliwa inayomilikiwa na kuendeshwa na NIT Africa Solutions 
Limited.

Dealers in: Installation, Maintenance And Sales Of Optic Fiber Cables, Its Accessories and General Telecommunication Networks, ICT and Security Systems.function setLanguage(lang) {const body = document.body;const btnEn = document.getElementById('btn-en');const btnSw = document.getElementById('btn-sw');if (lang === 'sw') {body.className = 'mode-sw';btnSw.classList.add('active');btnEn.classList.remove('active');} else {body.className = 'mode-en';btnEn.classList.add('active');btnSw.classList.remove('active');}}

<FollowUp>
Would you like me to help you set up an **automated contact form box** inside the contact section next so clients can send text inquiries straight to your phone or corporate emails?
</FollowUp>


