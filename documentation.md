
# BricksLift A/B Testing - Technický Blueprint Pluginu

**Verze Dokumentu:** 1.1
**Datum Poslední Aktualizace:** 18.5.2025
**Hlavní Architekt:** Adam Kotala

## 1. Úvod a Cíle Pluginu

**Název Pluginu:** BricksLift A/B Testing

**Hlavní Cíle:** Poskytnout uživatelům Bricks Builderu intuitivní a výkonný nástroj pro A/B testování prvků, sekcí a celých Bricks šablon přímo v editoru Bricks. Plugin umožní:
* Snadné vytváření a správu A/B testů.
* Definici více variant obsahu.
* Flexibilní nastavení konverzních cílů.
* Sběr dat o impresích a konverzích.
* Zobrazení přehledných statistik a základní vyhodnocení výkonu variant.
* Definování podmínek pro automatické ukončení testu.
* Poskytnutí doporučení pro implementaci vítězné varianty.
* Zajištění GDPR compliance a minimálního dopadu na výkon webu.

**Cílová Skupina:** Uživatelé WordPressu využívající Bricks Builder, kteří chtějí optimalizovat své webové stránky a zvyšovat konverze (marketéři, designéři, majitelé e-shopů, agentury).

## 2. Architektura a Technologie

* **Backend:** WordPress, PHP 7.4+
    * Správa testů: Custom Post Type (CPT)
    * Administrační rozhraní: React (s využitím `@wordpress/scripts`, `@wordpress/components`)
    * Komunikace: WordPress REST API, WordPress AJAX API
* **Frontend (Integrace s Bricks):** Vlastní Bricks element (`blft_test_wrapper`)
* **Frontend (Logika Zobrazení a Sledování):** Čistý JavaScript (ES6+).
* **Databáze:** WordPress DB (`wpdb`), vlastní tabulky pro sledování a agregované statistiky.
* **Styling:** CSS3, BEM metodika.
* **Build Process (pro React):** `@wordpress/scripts`.
* **PHP Autoloading:** Composer PSR-4.
* **Default language:** English.

## 3. Naming Konvence

* **PHP Namespace:** `BricksLiftAB`
* **Hlavní Prefix:** `blft_`
* **PHP Třídy:** `CamelCase` v rámci namespace (např. `BricksLiftAB\Core\CPT_Manager`)
* **CPT Slug:** `blft_test`
* **Databázové Tabulky:** `{wp_prefix}blft_tracking`, `{wp_prefix}blft_stats_aggregated`
* **Meta Klíče:** `_blft_internal_meta_key`
* **JavaScript Globální Objekt:** `BricksLiftAB.Frontend`
* **HTML Data Atributy:** `data-blft-test-id`, `data-blft-variant-id`
* **CSS Třídy:** `blft-kebab-case-class-name` (např. `blft-variant-hidden`)
* **REST API Namespace:** `blft/v1`
* **PHP Konstanty:** `BLFT_UPPER_SNAKE_CASE` (např. `BLFT_VERSION`)

## 4. Datový Model

### 4.1. Custom Post Type: `blft_test`

* **Slug:** `blft_test`
* **Podporuje:** `title`
* **Meta Pole:**
    * `_blft_status` (string): `draft`, `running`, `paused`, `completed`
    * `_blft_description` (text)
    * `_blft_variants` (JSON array): `[{"id": "var_uuid1", "name": "Varianta A", "distribution": 50}, ...]`
    * `_blft_goal_type` (string): `page_visit`, `selector_click`, `form_submission`, `wc_add_to_cart`, `scroll_depth`, `time_on_page`, `custom_js_event`.
    * **Specifická meta pole pro typy cílů:**
        * `_blft_goal_pv_url` (string), `_blft_goal_pv_url_match_type` (string)
        * `_blft_goal_sc_element_selector` (string)
        * `_blft_goal_fs_form_selector` (string), `_blft_goal_fs_trigger` (string), `_blft_goal_fs_thank_you_url` (string), `_blft_goal_fs_success_class` (string)
        * `_blft_goal_wc_any_product` (boolean), `_blft_goal_wc_product_id` (int)
        * `_blft_goal_sd_percentage` (int)
        * `_blft_goal_top_seconds` (int)
        * `_blft_goal_cje_event_name` (string)
    * `_blft_run_tracking_globally` (boolean)
    * `_blft_gdpr_consent_required` (boolean)
    * `_blft_gdpr_consent_mechanism` (string): `none`, `cookie_key`.
    * `_blft_gdpr_consent_key_name` (string)
    * `_blft_gdpr_consent_key_value` (string)
    * `_blft_test_duration_type` (string): `manual`, `time_based`, `impression_based`, `conversion_based`.
    * `_blft_test_duration_days` (int), `_blft_test_duration_target_impressions` (int), `_blft_test_duration_target_conversions` (int)
    * `_blft_test_start_date` (datetime), `_blft_test_actual_end_date` (datetime)
    * `_blft_test_winner_variant_id` (string)

### 4.2. Vlastní Databázové Tabulky

* **`{wp_prefix}blft_tracking`**: `id`, `test_id`, `variant_id`, `visitor_hash`, `event_type`, `event_timestamp`, `page_url`.
* **`{wp_prefix}blft_stats_aggregated`**: `test_id`, `variant_id`, `stat_date`, `impressions_count`, `conversions_count`.

## 5. Klíčové Funkcionality (Shrnutí)

* **Admin Rozhraní (React):** Dashboard testů, editor testů (varianty, cíle, GDPR, trvání), zobrazení statistik (grafy, tabulky, základní vyhodnocení).
* **Integrace s Bricks Builderem:** Vlastní element `blft_test_wrapper` pro definici obsahu variant.
* **Frontend Logika (JS):** Výběr a zobrazení varianty (localStorage, minimalizace flickeringu), sledování impresí a konverzí (AJAX), GDPR kontrola.
* **Životní Cyklus Testu:** Manuální/automatické ukončení (čas, imprese, konverze), akce po dokončení (doporučení, V2+: "Zamknout vítěze").
* **Zpracování Dat:** Denní cron pro agregaci statistik.

## 6. Bezpečnost, Výkon a Ostatní Aspekty

* **Bezpečnost:** Nonces, sanitizace vstupů, escapování výstupů, oprávnění, `$wpdb->prepare`.
* **Výkon:** Efektivní DB dotazy, asynchronní JS, optimalizované assety, podmíněné načítání.
* **Internacionalizace:** Připraveno pro překlad, `.pot` soubor.
* **Instalace/Odinstalace:** Správa DB tabulek, CPT dat.

## 7. Struktura Souborů a Složek

- `/brickslift-ab-testing/`
    - `brickslift-ab-testing.php`  # Hlavní soubor pluginu
    - `uninstall.php`
    - `readme.txt`
    - `composer.json`
    - `src/`                       # PHP kód s namespacem BricksLiftAB
        - `Core/`
            - `Plugin.php`
            - `CPT_Manager.php`
            - `DB_Manager.php`
            - `Cron_Manager.php`
            - `Activation_Handler.php`
            - `Deactivation_Handler.php`
        - `Admin/`
            - `Admin_Controller.php`
            - `Assets_Manager.php`
        - `Frontend/`
            - `Frontend_Controller.php`
            - `Ajax_Handler.php`
        - `Integrations/`
            - `Bricks/`
                - `Bricks_Integration_Loader.php`
                - `Element_Test_Wrapper.php`
            - `WooCommerce/` (volitelně)
            - `Forms/` (volitelně)
        - `API/`
            - `REST_Controller.php`
            - `Endpoints/`
                - `Stats_Endpoint.php`
                - `Active_Tests_Endpoint.php`
        - `Utils/`
            - `GDPR_Helper.php`
            - `Data_Sanitizer.php`
    - `admin-ui/`                  # React aplikace pro admin rozhraní
        - `build/`                 # Zkompilované soubory
        - `src/`                   # Zdrojové kódy Reactu (index.js, App.js, components/, pages/, store/, services/, atd.)
        - `package.json`
    - `frontend/`                  # Assety pro veřejnou část webu
        - `js/`
            - `blft-main.js`
        - `css/`
            - `blft-main.css`
    - `languages/`
        - `brickslift-ab-testing.pot`
    - `vendor/`                    # Composer závislosti


## 8. Fáze Vývoje (Roadmapa)

### Fáze 1: Základy a Architektura (Core Setup)
* **1.1:** Základní struktura pluginu, vývojové prostředí, Composer autoloading.
* **1.2:** Registrace CPT `blft_test` s minimálními meta poli.
* **1.3:** Vytvoření vlastních DB tabulek (`blft_tracking`, `blft_stats_aggregated`) při aktivaci.
* **1.4:** Nastavení React Admin UI (build proces, enqueue, základní root komponenta).

### Fáze 2: Správa Testů (Admin UI a Základní Logika)
* **2.1:** React: Seznam testů (Dashboard) - načítání přes REST API.
* **2.2:** React: Editor testů - základní pole (název, status, popis, varianty s distribucí). Ukládání přes REST API.
* **2.3:** React: Editor testů - rozšíření o nastavení cílů konverze (všechny typy).
* **2.4:** React: Editor testů - rozšíření o GDPR nastavení a globální sledování.

### Fáze 3: Integrace s Bricks Builderem a Frontend Logika (Zobrazení Variant)
* **3.1:** Vytvoření Bricks elementu `blft_test_wrapper` (výběr testu, dynamické sloty pro varianty, renderování s data-atributy a defaultním skrytím variant).
* **3.2:** Frontend JS (`blft-main.js`): Základní logika výběru varianty (visitor_hash, localStorage), zobrazení/skrytí variantních kontejnerů. Minimalizace "flickeringu".

### Fáze 4: Sledování Událostí (Imprese, Konverze) a GDPR
* **4.1:** Frontend JS: Sledování impresí (jednou za session), AJAX odeslání. Backend AJAX handler pro uložení do `blft_tracking`.
* **4.2:** Frontend JS: Implementace GDPR kontroly (`checkGDPRConsent`) - nesledovat bez souhlasu.
* **4.3:** Frontend JS: Sledování konverzí pro všechny definované typy cílů. AJAX odeslání. Backend AJAX handler pro uložení.

### Fáze 5: Zpracování Dat a Statistiky
* **5.1:** WP Cron Job: `blft_aggregate_daily_stats` pro denní agregaci dat z `blft_tracking` do `blft_stats_aggregated`.
* **5.2:** REST API: Endpoint `blft/v1/stats/<test_id>` pro načítání agregovaných statistik.
* **5.3:** React: Komponenta pro zobrazení statistik (tabulky, grafy, CR, základní identifikace vítěze).

### Fáze 6: Životní Cyklus Testu
* **6.1:** React: Editor testů - nastavení doby trvání a podmínek ukončení (`_blft_test_duration_type` a související pole).
* **6.2:** WP Cron Job: Rozšíření o logiku pro automatické ukončování testů (změna statusu na `completed`, notifikace admina).
* **6.3:** Frontend JS: Respektování statusu `completed` (zastavení výběru variant). Admin UI: Zobrazení výsledků dokončeného testu a doporučení pro manuální implementaci vítěze.

### Fáze 7: Dokončovací Práce, Testování a Vydání
* **7.1:** Bezpečnostní audit a optimalizace výkonu (PHP i JS).
* **7.2:** Internacionalizace (všechny řetězce, `.pot` soubor).
* **7.3:** Důkladné manuální a (pokud možno) automatizované testování napříč prostředími.
* **7.4:** Vytvoření uživatelské dokumentace.
* **7.5:** Příprava na vydání (readme, screenshoty, testování aktualizací).