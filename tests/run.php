<?php

declare(strict_types=1);

$sessionPath = sys_get_temp_dir() . '/lunettistar-test-sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);

require_once dirname(__DIR__) . '/config/app.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertTrue(class_exists(App::class), 'The application class should be available through the application autoloader.');
assertTrue(class_exists(Database::class), 'The database class should be available through the application autoloader.');

$content = [
    'personal' => [
        'full_name' => 'Emmanuel Baah',
        'email' => 'emmanuel@example.com',
        'phone' => '+233 24 000 0000',
        'location' => 'Accra, Ghana',
    ],
    'summary' => str_repeat('Experienced software engineer delivering reliable applications and measurable results. ', 2),
    'experience' => [[
        'role' => 'Software Engineer',
        'company' => 'Example Ltd',
        'bullets' => [
            'Improved order completion by 25% through a streamlined checkout.',
            'Developed a secure inventory service used by 5 employees.',
        ],
    ]],
    'education' => [['degree' => 'Diploma in Software Engineering', 'school' => 'IPMC']],
    'skills' => array_map(static fn (string $name): array => ['name' => $name], ['PHP', 'MySQL', 'Python', 'React', 'JavaScript', 'Git']),
];

$report = (new AtsService())->analyze($content, 'We need a software engineer with PHP, MySQL, JavaScript, Git and inventory experience.');
assertTrue($report['score'] >= 70, 'A complete, relevant CV should achieve a strong ATS score.');
assertTrue(in_array('php', $report['matched_keywords'], true), 'PHP should be detected as a matched keyword.');

$validator = new Validator();
assertTrue(!$validator->validate(['email' => 'not-an-email'], ['email' => 'required|email']), 'Invalid email should fail validation.');
assertTrue(isset($validator->errors()['email']), 'Email validation should expose a field error.');

$request = new Request(
    ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => BASE_PATH . '/dashboard', 'HTTP_ACCEPT' => 'text/html'],
    [],
    [],
    [],
    []
);
assertTrue($request->path() === '/dashboard', 'Request should remove the configured application base path.');
assertTrue(strlen(Csrf::token()) === 64, 'CSRF tokens should contain 32 random bytes.');

$typedRequest = new Request(
    ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => BASE_PATH . '/test'],
    [],
    ['unsafe' => ['nested'], 'enabled' => 'on', 'count' => 3],
    [],
    []
);
assertTrue($typedRequest->string('unsafe', 'fallback') === 'fallback', 'String input should reject nested values.');
assertTrue($typedRequest->string('count') === '3', 'String input should safely accept numeric values.');
assertTrue($typedRequest->boolean('enabled'), 'Boolean input should understand HTML checkbox values.');

$router = new Router();
$router->get('/health/{name}', static fn (Request $request, string $name): Response => Response::json([
    'success' => true,
    'name' => $name,
]));
$routeRequest = new Request(
    ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => BASE_PATH . '/health/lunetti', 'HTTP_ACCEPT' => 'application/json'],
    [],
    [],
    [],
    []
);
ob_start();
$router->dispatch($routeRequest)->send();
$routePayload = json_decode((string) ob_get_clean(), true);
assertTrue(($routePayload['name'] ?? null) === 'lunetti', 'Router should bind named path parameters.');

$assistant = (new AIService())->run('bullet', [
    'text' => 'responsible for daily customer support',
    'outcome' => 'a 20% faster response time',
], ['content' => []]);
assertTrue(str_contains($assistant['text'], '20%'), 'Achievement writer should preserve a supplied measurable result.');

$catalogue = TemplateCatalog::keys();
assertTrue(count($catalogue) === 18, 'The catalogue should expose every CV template. Got: ' . count($catalogue));
assertTrue(TemplateCatalog::layout('classic') === 'stacked', 'Classic should be a single column template.');
assertTrue(TemplateCatalog::layout('executive') === 'sidebar', 'Executive should keep its two column layout.');
assertTrue(TemplateCatalog::order('tech') === 'skills_first', 'Technical CVs should lead with skills.');
assertTrue(TemplateCatalog::order('spectrum') === 'skills_first', 'A template built around skills should lead with them.');

// The catalogue, the seed data and the browser renderer each list the
// templates separately, and a template missing from any one of them is either
// unpickable or unstyled. Hard-coding the count above is not enough.
$seed = (string) file_get_contents(__DIR__ . '/../database/schema.sql');
$script = (string) file_get_contents(__DIR__ . '/../public/assets/resume/renderer.js');
$styles = (string) file_get_contents(__DIR__ . '/../public/assets/resume/preview.css');
foreach ($catalogue as $key) {
    assertTrue(
        str_contains($seed, "('" . $key . "', '"),
        'Template ' . $key . ' should be seeded so it can be chosen.'
    );
    assertTrue(
        (bool) preg_match('/^\s+' . preg_quote($key, '/') . ':\s*\{/m', $script),
        'Template ' . $key . ' should be known to the renderer.'
    );
    assertTrue(
        str_contains($styles, '.cv-template-' . $key . ' .cv-skill'),
        'Template ' . $key . ' should set its skills in its own way.'
    );
}

$stackedDefaults = ResumeService::defaultContent();
assertTrue(
    $stackedDefaults['settings']['layout'] === 'stacked',
    'A new CV should default to the single column layout.'
);

$importer = new CvImportService();
$importedText = <<<'CV'
SARAH MENSAH
Senior Product Designer
Kumasi, Ghana | +233 20 555 1234 | sarah.mensah@example.com

PROFESSIONAL SUMMARY
Product designer with eight years of experience leading design for fintech and
logistics products across West Africa.

WORK EXPERIENCE

Senior Product Designer
Nimbus Financial | Accra, Ghana | Jan 2021 - Present
- Led the redesign of the payments dashboard, cutting support tickets by 34%.
- Built the design system used by four product teams.

EDUCATION

BSc Computer Science
Kwame Nkrumah University of Science and Technology | 2013 - 2017

TECHNICAL SKILLS
Figma, Sketch, HTML, CSS, User Research

LANGUAGES
English (Fluent), Twi (Native)
CV;

$imported = $importer->fromText($importedText);
$importedContent = $imported['content'];
assertTrue(
    $importedContent['personal']['full_name'] === 'Sarah Mensah',
    'Import should read the name from the top of a CV.'
);
assertTrue(
    $importedContent['personal']['email'] === 'sarah.mensah@example.com',
    'Import should read the email address.'
);
assertTrue(
    $importedContent['personal']['location'] === 'Kumasi, Ghana',
    'Import should keep a city and country together.'
);
assertTrue(
    count($importedContent['experience']) === 1 && $importedContent['experience'][0]['current'],
    'An open ended role should import as the current position.'
);
assertTrue(
    $importedContent['experience'][0]['company'] === 'Nimbus Financial',
    'Import should separate the employer from the job title.'
);
assertTrue(
    count($importedContent['experience'][0]['bullets']) === 2,
    'Import should keep each achievement bullet.'
);
assertTrue(
    $importedContent['education'][0]['school'] === 'Kwame Nkrumah University of Science and Technology',
    'Import should read the institution from an education entry.'
);
assertTrue(count($importedContent['skills']) === 5, 'Import should split a comma separated skills line.');
assertTrue(
    $importedContent['languages'][0]['name'] === 'English' && $importedContent['languages'][0]['level'] === 'Fluent',
    'Import should split a language from its proficiency.'
);
assertTrue(
    (int) $imported['detected']['experience'] === 1,
    'The import summary should report what was detected for review.'
);

$importFailed = false;
try {
    $importer->fromText('too short');
} catch (RuntimeException) {
    $importFailed = true;
}
assertTrue($importFailed, 'Import should refuse text too short to contain a CV.');

// A PDF built from a single uncompressed content stream, which is enough to
// exercise the operator walk without shipping a binary fixture.
$pdfBody = "BT /F1 12 Tf 72 720 Td (Kwabena Owusu) Tj 0 -18 Td (Software Engineer) Tj "
    . "0 -18 Td [(kwabena) -300 (at example.com)] TJ ET";
$pdf = "%PDF-1.4\n1 0 obj\n<< /Length " . strlen($pdfBody) . " >>\nstream\n" . $pdfBody . "\nendstream\nendobj\n";
$pdfText = PdfTextExtractor::extract($pdf);
assertTrue(str_contains($pdfText, 'Kwabena Owusu'), 'PDF extraction should read literal strings.');
assertTrue(str_contains($pdfText, 'Software Engineer'), 'PDF extraction should follow line moves.');
assertTrue(
    str_contains($pdfText, 'kwabena at example.com'),
    'PDF extraction should turn a wide kerning gap into a space.'
);

// A CV whose sections BrightCV has no equivalent for. These used to be filed
// under whichever heading came before them, turning publications and awards
// into jobs and "Date of birth" into a skill.
$mixedCv = <<<'CV'
KWAME ASANTE
Research Engineer
Kumasi, Ghana | +233 24 777 8888 | kwame.asante@example.com

PROFESSIONAL SUMMARY
Research engineer with seven years of experience in embedded systems and signal
processing for industrial monitoring equipment.

WORK EXPERIENCE

Senior Research Engineer
Ashanti Instruments | Kumasi | Mar 2019 - Present
- Designed the vibration analysis pipeline now running on 400 field units.

PUBLICATIONS
Asante, K. and Mensah, J. (2023). Low power vibration sensing for rotating machinery.

VOLUNTEER EXPERIENCE
STEM Mentor, Kumasi Girls Coding Club, 2020 - Present

AWARDS AND HONOURS
Best Paper Award, ICEE 2021

EDUCATION

MSc Electrical Engineering
KNUST | 2014 - 2016

TECHNICAL SKILLS
C, C++, Python, MATLAB, Embedded Linux

PERSONAL DETAILS
Date of birth: 14 March 1990
Nationality: Ghanaian
CV;

$mixed = $importer->fromText($mixedCv);
$mixedContent = $mixed['content'];

assertTrue(
    count($mixedContent['experience']) === 1,
    'Only the real job should import; publications, volunteering, and awards are not experience.'
);
assertTrue(
    $mixedContent['experience'][0]['role'] === 'Senior Research Engineer',
    'The one imported role should be the job, not a section heading.'
);
foreach ($mixedContent['skills'] as $skill) {
    assertTrue(
        stripos($skill['name'], 'nationality') === false && stripos($skill['name'], 'date of birth') === false,
        'Personal details must never be imported as skills.'
    );
}
assertTrue(count($mixedContent['skills']) === 5, 'Only the listed technical skills should import.');
assertTrue(
    $mixedContent['certifications'][0]['name'] === 'Best Paper Award',
    'An award belongs with certifications, the nearest thing BrightCV has to a credential.'
);
assertTrue(
    $mixedContent['education'][0]['degree'] === 'MSc Electrical Engineering',
    'Education should survive a skipped section appearing before it.'
);

$skippedHeadings = $mixed['detected']['skipped'];
foreach (['Publications', 'Volunteer Experience', 'Personal Details'] as $heading) {
    assertTrue(
        in_array($heading, $skippedHeadings, true),
        'The review summary should name "' . $heading . '" as not imported.'
    );
}

// A heading this parser has never heard of still has to stop the section above
// it from swallowing the content.
$unknownHeadingCv = <<<'CV'
ADWOA BOATENG
Operations Lead
Accra, Ghana | adwoa@example.com

EXPERIENCE

Operations Lead
Harbour Freight | Tema | 2021 - Present
- Cut turnaround time by a quarter.

BOARD POSITIONS
Trustee, Tema Harbour Trust, 2022 - Present

SKILLS
Logistics, Planning, Vendor Management
CV;

$unknown = $importer->fromText($unknownHeadingCv);
assertTrue(
    count($unknown['content']['experience']) === 1,
    'An unrecognised shouting heading should not add its section to the previous one.'
);
assertTrue(
    in_array('Board Positions', $unknown['detected']['skipped'], true),
    'An unrecognised heading should be reported rather than silently dropped.'
);

// A project link is not the writer's personal site.
$linkCv = <<<'CV'
YAA ANTWI
Developer
Accra, Ghana | yaa@example.com | github.com/yaaantwi

EXPERIENCE

Developer
Studio Nine | 2020 - Present
- Shipped the booking service.

PROJECTS
Ledger Tool, https://ledger.example.com
CV;

$links = $importer->fromText($linkCv);
assertTrue(
    str_contains($links['content']['personal']['website'], 'yaaantwi'),
    'The personal site should come from the contact block, not from a project link.'
);

// A short tagline under the name is not a professional summary.
$taglineCv = <<<'CV'
KOJO DARKO
Building reliable payment systems

Accra, Ghana | kojo@example.com

EXPERIENCE

Engineer
Paylink | 2019 - Present
- Built the settlement service.

SKILLS
Go, PostgreSQL, Kubernetes
CV;

$tagline = $importer->fromText($taglineCv);
assertTrue(
    $tagline['content']['summary'] === '',
    'A six word tagline should not be promoted into the summary.'
);

// Producers place each block with the graphics matrix and then position text
// inside it with the text matrix. Reading only the text matrix reported every
// block at the same spot and collapsed the page onto a handful of lines.
$blockPositioned = "%PDF-1.4\n1 0 obj\n<< >>\nstream\n"
    . "q 1 0 0 1 78 700 cm BT 1 0 0 1 0 4 Tm 18 TL /F2 14 Tf (Professional Summary) Tj T* ET Q\n"
    . "q 1 0 0 1 78 660 cm BT 1 0 0 1 0 4 Tm 12 TL /F1 10 Tf (Engineer with nine years of experience.) Tj T* ET Q\n"
    . "q 1 0 0 1 78 620 cm BT 1 0 0 1 0 4 Tm 18 TL /F2 14 Tf (Technical Skills) Tj T* ET Q\n"
    . "q 1 0 0 1 78 590 cm BT 1 0 0 1 0 4 Tm 12 TL /F1 10 Tf (PHP, MySQL, JavaScript) Tj T* ET Q\n"
    . "endstream\nendobj\n";
$blockText = PdfTextExtractor::extract($blockPositioned);
// Extraction marks the lines a page set as section headings, so comparisons
// of the words strip the mark and the marking itself is asserted on its own.
$stripMark = static fn (string $line): string => trim(str_replace(PdfTextExtractor::HEADING_MARK, '', $line));
assertTrue(
    str_contains($blockText, PdfTextExtractor::HEADING_MARK . 'Professional Summary'),
    'Type larger than the body should mark a line as a section heading.'
);
assertTrue(
    !str_contains($blockText, PdfTextExtractor::HEADING_MARK . 'Engineer with nine'),
    'Body text should not be marked as a heading.'
);
$blockLines = array_values(array_filter(array_map($stripMark, explode("\n", $blockText))));
assertTrue(
    $blockLines === ['Professional Summary', 'Engineer with nine years of experience.', 'Technical Skills', 'PHP, MySQL, JavaScript'],
    'Blocks placed with the graphics matrix should come back as separate lines in page order.'
);

// A sidebar CV is written row by row across both columns. Following the stream
// blindly interleaves the sidebar with the main content.
$sidebar = "%PDF-1.4\n1 0 obj\n<< >>\nstream\nBT /F1 10 Tf\n";
$sideRows = ['SKILLS', 'Python', 'Django', 'Docker', 'SQL', 'Redis', 'Linux'];
$mainRows = ['EXPERIENCE', 'Backend Developer', 'Sika Pay 2020', 'Built the settlement service', 'Cut latency in half', 'EDUCATION', 'BSc Computing'];
foreach ($sideRows as $index => $row) {
    $y = 700 - $index * 20;
    $sidebar .= sprintf("1 0 0 1 %d %d Tm (%s) Tj\n", 50, $y, $row);
    $sidebar .= sprintf("1 0 0 1 %d %d Tm (%s) Tj\n", 300, $y, $mainRows[$index]);
}
$sidebar .= "ET\nendstream\nendobj\n";
$sidebarText = PdfTextExtractor::extract($sidebar);
$sidebarLines = array_values(array_filter(array_map($stripMark, explode("\n", $sidebarText))));
assertTrue(
    array_slice($sidebarLines, 0, 7) === $sideRows,
    'The narrow column should be read through before the wide one, not interleaved with it.'
);
assertTrue(
    array_slice($sidebarLines, 7, 7) === $mainRows,
    'The main column should follow the sidebar in its own reading order.'
);

// Right aligned dates also produce two clusters of x positions, but they are
// not a second column and must stay on the line they were drawn on.
$rightAligned = "%PDF-1.4\n1 0 obj\n<< >>\nstream\nBT /F1 10 Tf\n";
$roles = ['Senior Analyst', 'Analyst', 'Junior Analyst', 'Intern', 'Assistant'];
$dates = ['2021 - Present', '2019 - 2021', '2017 - 2019', '2016 - 2017', '2015 - 2016'];
foreach ($roles as $index => $role) {
    $y = 700 - $index * 20;
    $rightAligned .= sprintf("1 0 0 1 %d %d Tm (%s) Tj\n", 56, $y, $role);
    $rightAligned .= sprintf("1 0 0 1 %d %d Tm (%s) Tj\n", 460, $y, $dates[$index]);
}
$rightAligned .= "ET\nendstream\nendobj\n";
$rightLines = array_values(array_filter(array_map($stripMark, explode("\n", PdfTextExtractor::extract($rightAligned)))));
assertTrue(
    $rightLines[0] === 'Senior Analyst 2021 - Present',
    'A right aligned date belongs on the same line as its role, not in a second column.'
);
assertTrue(count($rightLines) === 5, 'Right aligned dates should not split the page into columns.');

// The bullet glyph is written as 0x7F by some producers and 0x95 by others.
// Left as a control character it made every achievement look like a new job.
$bulleted = "%PDF-1.4\n1 0 obj\n<< >>\nstream\n"
    . "q 1 0 0 1 60 700 cm BT 1 0 0 1 0 0 Tm 14 TL /F1 10 Tf (EXPERIENCE) Tj T* ET Q\n"
    . "q 1 0 0 1 60 680 cm BT 1 0 0 1 0 0 Tm 14 TL /F1 10 Tf (Backend Developer) Tj T* ET Q\n"
    . "q 1 0 0 1 60 664 cm BT 1 0 0 1 0 0 Tm 14 TL /F1 10 Tf (Sika Pay | Accra | Mar 2020 - Present) Tj T* ET Q\n"
    . "q 1 0 0 1 60 648 cm BT 1 0 0 1 0 0 Tm 14 TL /F1 10 Tf (\x7f Built the settlement service.) Tj T* ET Q\n"
    . "q 1 0 0 1 60 632 cm BT 1 0 0 1 0 0 Tm 14 TL /F1 10 Tf (\x7f Cut latency in half.) Tj T* ET Q\n"
    . "endstream\nendobj\n";
$bulletText = PdfTextExtractor::extract($bulleted);
assertTrue(
    str_contains($bulletText, "\u{2022} Built the settlement service."),
    'A bullet written as a control byte should decode to a real bullet character.'
);

$bulletParsed = $importer->fromText(
    "AKOSUA BOATENG\nBackend Developer\nAccra, Ghana | akosua@example.com\n\n" . $bulletText
);
assertTrue(
    count($bulletParsed['content']['experience']) === 1,
    'Bulleted achievements belong to the role above them, not to jobs of their own.'
);
assertTrue(
    count($bulletParsed['content']['experience'][0]['bullets']) === 2,
    'Both achievement lines should attach to the role as bullets.'
);

// A qualification carries a comma of its own. Splitting on it turned
// "MBA, Marketing" into a degree at an institution called Marketing, and left
// the real institution to start an entry of its own.
$comma = $importer->fromText(<<<'CV'
KOJO ANNAN
Marketing Lead
Accra, Ghana | kojo.annan@example.com

EXPERIENCE

Marketing Lead
Kete Foods | Accra, Ghana | 2021 - Present
- Ran the rebrand.

EDUCATION

MBA, Marketing
University of Ghana Business School | 2016 - 2018

SKILLS
Brand Strategy, SEO
CV);
$commaEducation = $comma['content']['education'];
assertTrue(count($commaEducation) === 1, 'A degree and its institution are one entry, not two.');
assertTrue($commaEducation[0]['degree'] === 'MBA, Marketing', 'The comma belongs to the qualification.');
assertTrue(
    $commaEducation[0]['school'] === 'University of Ghana Business School',
    'The institution on the following line should fill the same entry.'
);
assertTrue(
    $comma['content']['experience'][0]['company'] === 'Kete Foods'
        && $comma['content']['experience'][0]['location'] === 'Accra, Ghana',
    'Once the role is known a comma may separate the employer from its location.'
);

// Real CVs rarely use the bare noun. Matching on the telling word covers far
// more wording than any list of exact phrases.
$wordedCv = <<<'CV'
NANA AKUA DARKO
Supply Chain Analyst
Tema, Ghana | +233 55 212 7788 | nana.darko@example.com

Career Profile & Objective
Supply chain analyst with seven years of experience in procurement and demand
planning for fast moving consumer goods across the region.

Professional Experience & Achievements

Supply Chain Analyst
Kasapreko Company | Tema | May 2020 - Present
- Cut stockouts by 34 percent across eleven distribution centres.

Education and Professional Qualifications

BSc Logistics and Supply Chain Management
Kwame Nkrumah University of Science and Technology | 2013 - 2017

Areas of Technical Competence
SAP MM, Demand Planning, Excel, Power BI, Vendor Negotiation

Licences & Professional Training
CIPS Level 4 | Chartered Institute of Procurement and Supply | 2021

Spoken Languages
English (Fluent), Twi (Native)
CV;

$worded = $importer->fromText($wordedCv);
$wordedContent = $worded['content'];
assertTrue(
    str_starts_with($wordedContent['summary'], 'Supply chain analyst'),
    '"Career Profile & Objective" is a summary heading, not an experience one.'
);
assertTrue(count($wordedContent['experience']) === 1, '"Professional Experience & Achievements" is the experience section.');
assertTrue(count($wordedContent['education']) === 1, '"Education and Professional Qualifications" is the education section.');
assertTrue(count($wordedContent['skills']) === 5, '"Areas of Technical Competence" is the skills section.');
assertTrue(count($wordedContent['certifications']) === 1, '"Licences & Professional Training" belongs with certifications.');
assertTrue(count($wordedContent['languages']) === 2, '"Spoken Languages" is the languages section.');

// Headings dressed in rules, numbers, and colons.
$decorated = $importer->fromText(<<<'CV'
YAA ASANTEWAA
Data Scientist
Accra, Ghana | yaa.asantewaa@example.com

--- 1. PROFILE ---
Data scientist with six years building forecasting and churn models for banks
and telecoms across the region.

>> 2. EMPLOYMENT <<

Senior Data Scientist
MTN Ghana | Accra | Feb 2021 - Present
- Cut postpaid churn by 18 percent.

3. ACADEMIC BACKGROUND:

MSc Statistics
University of Ghana | 2016 - 2018

4. TOOLS I USE:
Python, R, SQL, Tableau
CV);
assertTrue(count($decorated['content']['experience']) === 1, 'A numbered, ruled heading still names its section.');
assertTrue(
    $decorated['content']['education'][0]['school'] === 'University of Ghana',
    'An institution line under a decorated heading fills the entry it belongs to.'
);
assertTrue(count($decorated['content']['skills']) === 4, 'A heading ending in a colon still names its section.');

// A word that names a section is only trusted on a line shaped like a heading,
// otherwise an entry line reads as one and swallows the entry.
assertTrue(
    (new ReflectionMethod(CvImportService::class, 'headingKey'))
        ->invoke($importer, 'University of Ghana Business School | 2016 - 2018') === null,
    'An entry line carrying a date is never a heading, whatever words are in it.'
);

// A heading that names the section already open is the title of an entry.
$sameKey = $importer->fromText(<<<'CV'
KOJO MENSAH
Researcher
Accra, Ghana | kojo.mensah@example.com

EDUCATION

MBA, Marketing
University of Ghana Business School | 2016 - 2018

BA Communication Studies
Kwame Nkrumah University of Science and Technology | 2011 - 2015

SKILLS
Research, Analysis, Reporting
CV);
$sameKeyEducation = $sameKey['content']['education'];
assertTrue(count($sameKeyEducation) === 2, 'Two qualifications should import as two entries.');
assertTrue(
    $sameKeyEducation[1]['degree'] === 'BA Communication Studies',
    'A degree whose name contains "Studies" is an entry title, not an education heading.'
);
assertTrue(
    $sameKeyEducation[1]['school'] === 'Kwame Nkrumah University of Science and Technology',
    'The institution should stay with the qualification above it.'
);

// A block under a heading that names nothing is placed by what it holds.
$infer = new ReflectionMethod(CvImportService::class, 'inferCategory');
assertTrue(
    $infer->invoke($importer, ['AutoCAD, Civil 3D, Structural Analysis, Site Supervision']) === 'skills',
    'A short list of short items with no dates is a list of skills.'
);
assertTrue(
    $infer->invoke($importer, ['Professional Engineer | Ghana Institution of Engineers | 2021']) === 'certifications',
    'A credential carries who issued it and the year.'
);
assertTrue(
    $infer->invoke($importer, ['BSc Civil Engineering', 'University of Cape Coast | 2012 - 2016']) === 'education',
    'A qualification beside a year is education.'
);
assertTrue(
    $infer->invoke($importer, [
        'Site Engineer',
        'Coastal Roads Limited | Cape Coast | Jul 2019 - Present',
        "\u{2022} Supervised eleven kilometres of resurfacing.",
    ]) === 'experience',
    'Dated entries with achievements under them are work experience.'
);
assertTrue(
    $infer->invoke($importer, ['Civil engineer with eight years designing and supervising road and drainage works.']) === 'summary',
    'A paragraph of prose with no dates is an opening statement.'
);
assertTrue(
    $infer->invoke($importer, ['Trustee, Tema Harbour Trust']) === null,
    'A block that matches nothing is reported rather than guessed into a section.'
);

// The reader marks the lines a page set as headings, and a heading whose
// wording says nothing is then placed by its contents.
$fontHeadings = "%PDF-1.4
1 0 obj
<< >>
stream
";
$fontRow = static function (int $y, int $pt, string $row): string {
    return sprintf(
        "q 1 0 0 1 60 %d cm BT 1 0 0 1 0 0 Tm 14 TL /F1 %d Tf (%s) Tj T* ET Q
",
        $y,
        $pt,
        $row
    );
};
// Body first, so the smaller size is plainly the size the page is set in.
for ($i = 0; $i < 6; $i++) {
    $fontHeadings .= $fontRow(760 - $i * 20, 10, sprintf('Body line %d with several ordinary words on it', $i));
}
$fontHeadings .= $fontRow(620, 13, 'What I am good at');
$fontHeadings .= $fontRow(600, 10, 'AutoCAD, Civil 3D, Structural Analysis, Site Supervision');
$fontHeadings .= $fontRow(570, 13, 'Things I have earned');
$fontHeadings .= $fontRow(550, 10, 'Professional Engineer | Ghana Institution of Engineers | 2021');
$fontHeadings .= "endstream\nendobj\n";

$fontText = PdfTextExtractor::extract($fontHeadings);
assertTrue(
    str_contains($fontText, PdfTextExtractor::HEADING_MARK . 'What I am good at'),
    'A line set larger than the body should be marked as a heading.'
);

$fontParsed = $importer->fromText(
    "KOFI BOATENG\nCivil Engineer\nCape Coast, Ghana | kofi.boateng@example.com\n\n" . $fontText
);
assertTrue(
    count($fontParsed['content']['skills']) === 4,
    'A heading no word list can match is placed by what sits under it.'
);
assertTrue(
    count($fontParsed['content']['certifications']) === 1,
    'The credential under "Things I have earned" belongs with certifications.'
);

// A subset font numbers its glyphs from one, so the bytes on the page are glyph
// numbers rather than characters. The font's ToUnicode table is the only thing
// that says which character each number stands for.
$cmap = <<<'CMAP'
/CIDInit /ProcSet findresource begin
begincmap
2 beginbfchar
<0003> <0020>
<0044> <0061>
endbfchar
1 beginbfrange
<0045> <0047> <0062>
endbfrange
endcmap
CMAP;
$parsed = PdfCmap::parse($cmap);
assertTrue($parsed[0x0044] === 'a', 'A bfchar entry names the character a glyph stands for.');
assertTrue($parsed[0x0003] === ' ', 'A bfchar entry may name a space.');
assertTrue(
    $parsed[0x0045] === 'b' && $parsed[0x0046] === 'c' && $parsed[0x0047] === 'd',
    'A bfrange counts up from its first character across the whole range.'
);

// Word writes list bullets from a symbol font, which land in the private use
// area. Left alone they read as stray accented letters at the head of a line.
$bulletCmap = "begincmap\n1 beginbfchar\n<0078> <F0B7>\nendbfchar\nendcmap";
assertTrue(
    PdfCmap::parse($bulletCmap)[0x78] === "\u{2022}",
    'A symbol font bullet should come back as a real bullet character.'
);

/** Assemble a small PDF from object bodies. */
$buildPdf = static function (array $objects): string {
    $pdf = "%PDF-1.5\n";
    foreach ($objects as $number => $body) {
        $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
    }
    return $pdf . "trailer\n<< >>\n";
};

// A page whose text is drawn with an Identity-H subset font.
$identityCmap = "begincmap\n5 beginbfchar\n<0024> <0048>\n<0048> <0065>\n<004F> <006C>\n"
    . "<0052> <006F>\n<0003> <0020>\nendbfchar\nendcmap";
$identityPdf = $buildPdf([
    1 => "<< /Type /Page /Contents 2 0 R /Resources << /Font << /F1 3 0 R >> >> >>",
    2 => "<< >>\nstream\nBT /F1 12 Tf 1 0 0 1 60 700 Tm <0024 0048 004F 004F 0052> Tj ET\nendstream",
    3 => "<< /Type /Font /Subtype /Type0 /BaseFont /ABCDEE+Cambria /Encoding /Identity-H /ToUnicode 4 0 R >>",
    4 => "<< >>\nstream\n" . $identityCmap . "\nendstream",
]);
assertTrue(
    str_contains(PdfTextExtractor::extract($identityPdf), 'Hello'),
    'Glyph numbers should be read back as words through the font\'s own table.'
);

// A marked content dictionary carries a string of its own. Read as page text it
// put three stray characters at the head of every line.
$markedPdf = $buildPdf([
    1 => "<< /Type /Page /Contents 2 0 R /Resources << /Font << /F1 3 0 R >> >> >>",
    2 => "<< >>\nstream\n/P <</MCID 0/Lang (en-US)>> BDC\nBT /F1 12 Tf 1 0 0 1 60 700 Tm (EMMANUEL BAAH) Tj ET\nEMC\nendstream",
    3 => "<< /Type /Font /Subtype /TrueType /BaseFont /ArialMT /Encoding /WinAnsiEncoding >>",
]);
$markedText = trim(str_replace(PdfTextExtractor::HEADING_MARK, '', PdfTextExtractor::extract($markedPdf)));
assertTrue(
    $markedText === 'EMMANUEL BAAH',
    'Only what is drawn between BT and ET is page text; a tagging dictionary is not. Got: ' . $markedText
);

// A word split across two text objects is one word, not two.
$splitPdf = $buildPdf([
    1 => "<< /Type /Page /Contents 2 0 R /Resources << /Font << /F1 3 0 R >> >> >>",
    2 => "<< >>\nstream\n"
        . "BT /F1 10 Tf 1 0 0 1 60 700 Tm (and prop) Tj ET\n"
        . "BT /F1 10 Tf 1 0 0 1 100 700 Tm (erty management) Tj ET\n"
        . "BT /F1 10 Tf 1 0 0 1 400 700 Tm (2021) Tj ET\n"
        . "endstream",
    3 => "<< /Type /Font /Subtype /TrueType /BaseFont /ArialMT /Encoding /WinAnsiEncoding >>",
]);
$splitText = PdfTextExtractor::extract($splitPdf);
assertTrue(str_contains($splitText, 'property management'), 'A word split across text objects should be rejoined.');
assertTrue(str_contains($splitText, 'management 2021'), 'A real gap on the page is still a space.');

// This CV lists the employer first and the job title under it. Reading order
// alone cannot tell that apart from the other way round, but the words can.
$employerFirst = $importer->fromText(<<<'CV'
EMMANUEL BAAH
Backend Software Engineer
Accra, Ghana | (+233) 599114334 | ebaah8822@gmail.com

PROFESSIONAL EXPERIENCE

National Health Insurance Authority (NHIA), Accra, Ghana
National Service Personnel | Nov 2023 - Oct 2024
- Provided accurate guidance to clients regarding health insurance services.

Anglican Junior High School, Wamfie, Ghana
Teaching Intern | Sep 2022 - Dec 2022
- Supported classroom instruction and record management.

EDUCATION
- Diploma in Software Engineering - IPMC University College (In Progress)
- Bachelor of Arts in Education (Education with Sociology) - University of Ghana

TECHNICAL SKILLS
Languages: Python, PHP, JavaScript
Tools: Git, GitHub, VS Code
CV);
$employerContent = $employerFirst['content'];

assertTrue(count($employerContent['experience']) === 2, 'Both roles should import.');
assertTrue(
    $employerContent['experience'][0]['role'] === 'National Service Personnel',
    'The job title belongs in the role, whichever line it was written on.'
);
assertTrue(
    $employerContent['experience'][0]['company'] === 'National Health Insurance Authority (NHIA)',
    'The organisation belongs in the employer field.'
);
assertTrue(
    $employerContent['experience'][0]['location'] === 'Accra, Ghana',
    'Where the employer is should be taken off the end of its name.'
);
assertTrue(
    $employerContent['experience'][1]['company'] === 'Anglican Junior High School',
    'A school named as an employer is not an education heading.'
);

// Education is often written as a bulleted list of qualifications, and each of
// those bullets is an entry rather than a note about the entry above it.
assertTrue(count($employerContent['education']) === 2, 'Both qualifications should import as entries.');
assertTrue(
    $employerContent['education'][0]['degree'] === 'Diploma in Software Engineering',
    'A separator the writer put in outranks a trailing bracket.'
);
assertTrue(
    $employerContent['education'][0]['school'] === 'IPMC University College (In Progress)',
    'The institution keeps the note written beside it.'
);

// A skills section grouped under labels lists skills, not labels.
$skillNames = array_column($employerContent['skills'], 'name');
assertTrue(in_array('Python', $skillNames, true), 'A grouped skill imports under its own name.');
assertTrue(
    !in_array('Languages: Python', $skillNames, true),
    'The group label is not part of the skill.'
);
assertTrue(count($skillNames) === 6, 'Every listed skill imports exactly once.');

// A project's technology line describes the project above it.
$projectCv = $importer->fromText(<<<'CV'
AMA MENSAH
Developer
Accra, Ghana | ama@example.com

SELECTED SOFTWARE PROJECTS
BrightCV (PHP MVC Resume Builder)
Technologies: PHP, MySQL, JavaScript
- Designed a modular resume builder using a custom MVC architecture.
- Implemented authentication, dashboard analytics, and PDF
generation.
GhanaRent (In Development)
Technologies: PHP, MySQL, MVC
- Developing a property rental platform.
CV);
$projects = $projectCv['content']['projects'];
assertTrue(count($projects) === 2, 'A technology line is part of a project, not a project of its own.');
assertTrue($projects[0]['name'] === 'BrightCV (PHP MVC Resume Builder)', 'The project keeps its own name.');
assertTrue(
    str_contains($projects[0]['description'], 'Technologies: PHP, MySQL, JavaScript'),
    'The technology line is kept as part of the description.'
);
assertTrue(
    str_contains($projects[0]['description'], 'PDF generation'),
    'A bullet wrapped onto a second line stays one sentence.'
);

// A CV may list more links than there are fields to hold them.
$manyLinks = $importer->fromText(<<<'CV'
EMMANUEL BAAH
Backend Software Engineer
Accra, Ghana | ebaah8822@gmail.com
GitHub: https://github.com/baahe0044-netizen
Portfolio: https://emmanuel-portfolio-murex.vercel.app/
LinkedIn: https://www.linkedin.com/in/emmanuel-baah-651a6b3a6/

PROFESSIONAL SUMMARY
Backend engineer with hands on experience building web applications for growing teams.
CV);
assertTrue(
    str_contains($manyLinks['content']['personal']['linkedin'], 'linkedin.com'),
    'The LinkedIn address fills the LinkedIn field.'
);
assertTrue(
    in_array('https://emmanuel-portfolio-murex.vercel.app/', $manyLinks['detected']['skipped'], true),
    'A link with no field to hold it is named in the review rather than dropped.'
);


// --- Mail -----------------------------------------------------------------
// Password reset is the only thing BrightCV emails, and a host that quietly
// refuses to send it looks identical to a working app until someone is locked
// out. These check the parts that decide whether a message can leave at all.

$mailLog = STORAGE_PATH . '/logs/mail.log';
$readMailLog = static function () use ($mailLog): string {
    return is_file($mailLog) ? (string) file_get_contents($mailLog) : '';
};
$mailLogBefore = strlen($readMailLog());

// The log driver is the default outside production, so a test run and a local
// clone never post mail to anyone.
putenv('MAIL_DRIVER=log');
$mailer = new MailService();
assertTrue(
    $mailer->send('someone@example.com', 'Reset your BrightCV password', '<p>Link</p>', 'Link'),
    'The log driver should accept a message.'
);
$logged = substr($readMailLog(), $mailLogBefore);
assertTrue(
    str_contains($logged, 'someone@example.com') && str_contains($logged, 'Reset your BrightCV password'),
    'A logged message should record who it was for and what it said.'
);

// An address that is not an address never reaches a transport.
assertTrue(
    !$mailer->send('not-an-address', 'Subject', '<p>x</p>', 'x'),
    'An invalid recipient should be refused before any sending is attempted.'
);

// A driver name that is not one of ours falls back to writing a log rather
// than picking a transport at random.
putenv('MAIL_DRIVER=carrier-pigeon');
$mailLogBefore = strlen($readMailLog());
assertTrue(
    (new MailService())->send('someone@example.com', 'Fallback', '<p>x</p>', 'x'),
    'An unknown driver should fall back to the log rather than failing.'
);
assertTrue(
    str_contains(substr($readMailLog(), $mailLogBefore), 'Fallback'),
    'The fallback should be the log driver.'
);

// A newline in a subject would let the rest of a header block be written by
// whoever supplied it.
putenv('MAIL_DRIVER=log');
$mailLogBefore = strlen($readMailLog());
(new MailService())->send(
    'someone@example.com',
    "Reset\r\nBcc: attacker@example.com",
    '<p>x</p>',
    'x'
);
$injected = substr($readMailLog(), $mailLogBefore);
assertTrue(
    !str_contains($injected, "\nBcc:") && str_contains($injected, 'Subject: Reset Bcc: attacker@example.com'),
    'A subject should be flattened to one line so it cannot add headers.'
);

// The API transport is the one that works on shared hosting, so its failure
// path has to be legible rather than a bare false.
$noKey = new HttpMailer('brevo', '');
assertTrue(
    !$noKey->send('someone@example.com', 'Subject', '<p>x</p>', 'x', 'from@example.com', 'BrightCV'),
    'Sending with no API key should fail.'
);
assertTrue(
    str_contains($noKey->error(), 'API key'),
    'The reason for a failed send should name the missing key. Got: ' . $noKey->error()
);

// An unknown provider falls back to a supported one instead of building a
// request no service understands.
assertTrue(
    in_array('brevo', HttpMailer::PROVIDERS, true) && in_array('resend', HttpMailer::PROVIDERS, true),
    'Both supported mail providers should be listed.'
);
assertTrue(
    in_array('api', MailService::DRIVERS, true) && in_array('log', MailService::DRIVERS, true),
    'The API and log drivers should be selectable.'
);

putenv('MAIL_DRIVER');


// The schema creates tables and not the database holding them. Shared hosting
// does not grant CREATE DATABASE, so a statement naming its own database fails
// on the first line of a phpMyAdmin import and the whole deployment stops.
$schemaSql = (string) file_get_contents(__DIR__ . '/../database/schema.sql');
assertTrue(
    !preg_match('/^\s*(CREATE\s+DATABASE|CREATE\s+SCHEMA|USE)\b/im', $schemaSql),
    'The schema should not create or select a database; the host does that.'
);
assertTrue(
    str_contains($schemaSql, 'CREATE TABLE IF NOT EXISTS users'),
    'The schema should still create the tables it is responsible for.'
);

echo "PHP domain tests passed.\n";
