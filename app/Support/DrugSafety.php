<?php

namespace App\Support;

use App\Models\MedicalHistory;
use Illuminate\Support\Collection;

/**
 * Rule-based medication safety checks: drug allergies, drug–drug interactions,
 * duplicate therapy and drug–condition cautions (hypertension, diabetes).
 *
 * This is a decision-support aid for the pharmacist, not a full drug database:
 * it only knows the common medicines listed below (generic + common Malaysian brands).
 * Unknown drugs are still checked against the allergy text by name.
 *
 * Each warning: ['severity' => 'major'|'moderate', 'type' => 'allergy'|'interaction'|'duplicate'|'condition',
 *                'title' => string, 'detail' => string]
 */
class DrugSafety
{
    /** generic => [aliases (brands / other spellings), classes] */
    private const DRUGS = [
        // Penicillins
        'amoxicillin' => [['amoxil', 'augmentin', 'co-amoxiclav', 'amoxycillin', 'moxilen'], ['penicillin']],
        'ampicillin' => [['unasyn'], ['penicillin']],
        'cloxacillin' => [[], ['penicillin']],
        'flucloxacillin' => [['floxapen'], ['penicillin']],
        'phenoxymethylpenicillin' => [['penicillin v', 'pen v', 'penicillin'], ['penicillin']],
        'benzylpenicillin' => [['penicillin g', 'pen g'], ['penicillin']],
        'piperacillin' => [['tazocin'], ['penicillin']],
        // Cephalosporins
        'cefalexin' => [['cephalexin', 'keflex'], ['cephalosporin']],
        'cefuroxime' => [['zinnat'], ['cephalosporin']],
        'ceftriaxone' => [['rocephin'], ['cephalosporin']],
        'cefixime' => [[], ['cephalosporin']],
        'cefaclor' => [[], ['cephalosporin']],
        // Macrolides
        'clarithromycin' => [['klacid'], ['macrolide']],
        'erythromycin' => [[], ['macrolide']],
        'azithromycin' => [['zithromax'], ['macrolide']],
        // Fluoroquinolones
        'ciprofloxacin' => [['ciprobay'], ['fluoroquinolone']],
        'levofloxacin' => [['tavanic'], ['fluoroquinolone']],
        'moxifloxacin' => [['avelox'], ['fluoroquinolone']],
        // Sulfonamide antibiotic (combination brand listed on both parts)
        'sulfamethoxazole' => [['bactrim', 'co-trimoxazole', 'cotrimoxazole', 'septrin'], ['sulfonamide']],
        'trimethoprim' => [['bactrim', 'co-trimoxazole', 'cotrimoxazole', 'septrin'], []],
        'metronidazole' => [['flagyl'], []],
        'fluconazole' => [['diflucan'], ['azole_antifungal']],
        // TB
        'rifampicin' => [['rifampin', 'rifinah', 'akurit'], []],
        'isoniazid' => [['rifinah', 'akurit'], []],
        // Analgesics / NSAIDs
        'paracetamol' => [['panadol', 'acetaminophen', 'uphamol'], []],
        'aspirin' => [['cardiprin', 'acetylsalicylic acid', 'disprin'], ['nsaid', 'antiplatelet', 'salicylate']],
        'ibuprofen' => [['brufen', 'nurofen'], ['nsaid']],
        'diclofenac' => [['voltaren', 'cataflam'], ['nsaid']],
        'naproxen' => [['naprosyn'], ['nsaid']],
        'mefenamic acid' => [['ponstan'], ['nsaid']],
        'celecoxib' => [['celebrex'], ['nsaid']],
        'etoricoxib' => [['arcoxia'], ['nsaid']],
        'indomethacin' => [['indocid'], ['nsaid']],
        'piroxicam' => [[], ['nsaid']],
        'ketoprofen' => [[], ['nsaid']],
        // Opioids
        'tramadol' => [['tramal'], ['opioid', 'serotonergic']],
        'codeine' => [[], ['opioid']],
        'morphine' => [[], ['opioid']],
        'oxycodone' => [['oxycontin'], ['opioid']],
        // Benzodiazepines
        'diazepam' => [['valium'], ['benzodiazepine']],
        'alprazolam' => [['xanax'], ['benzodiazepine']],
        'lorazepam' => [['ativan'], ['benzodiazepine']],
        'clonazepam' => [['rivotril'], ['benzodiazepine']],
        'midazolam' => [[], ['benzodiazepine']],
        // Antidepressants
        'fluoxetine' => [['prozac'], ['ssri', 'serotonergic']],
        'sertraline' => [['zoloft'], ['ssri', 'serotonergic']],
        'escitalopram' => [['lexapro'], ['ssri', 'serotonergic']],
        'citalopram' => [[], ['ssri', 'serotonergic']],
        'paroxetine' => [['seroxat'], ['ssri', 'serotonergic']],
        'lithium' => [[], []],
        // Anticoagulants / antiplatelets
        'warfarin' => [['coumadin', 'marevan'], ['anticoagulant']],
        'rivaroxaban' => [['xarelto'], ['anticoagulant']],
        'apixaban' => [['eliquis'], ['anticoagulant']],
        'dabigatran' => [['pradaxa'], ['anticoagulant']],
        'clopidogrel' => [['plavix'], ['antiplatelet']],
        'ticagrelor' => [['brilinta'], ['antiplatelet']],
        // Statins
        'simvastatin' => [['zocor'], ['statin']],
        'atorvastatin' => [['lipitor'], ['statin']],
        'rosuvastatin' => [['crestor'], ['statin']],
        'lovastatin' => [[], ['statin']],
        'pravastatin' => [[], ['statin']],
        // Blood pressure / heart
        'perindopril' => [['coversyl'], ['ace_inhibitor']],
        'enalapril' => [['renitec'], ['ace_inhibitor']],
        'lisinopril' => [['zestril'], ['ace_inhibitor']],
        'captopril' => [[], ['ace_inhibitor']],
        'ramipril' => [['tritace'], ['ace_inhibitor']],
        'losartan' => [['cozaar', 'hyzaar'], ['arb']],
        'valsartan' => [['diovan', 'exforge'], ['arb']],
        'telmisartan' => [['micardis'], ['arb']],
        'irbesartan' => [['aprovel'], ['arb']],
        'candesartan' => [['atacand'], ['arb']],
        'spironolactone' => [['aldactone'], ['potassium_sparing']],
        'amiloride' => [[], ['potassium_sparing']],
        'potassium chloride' => [['slow-k', 'slow k', 'potassium supplement'], ['potassium']],
        'amlodipine' => [['norvasc', 'exforge'], ['ccb']],
        'nifedipine' => [['adalat'], ['ccb']],
        'atenolol' => [['tenormin'], ['beta_blocker']],
        'metoprolol' => [['betaloc'], ['beta_blocker']],
        'bisoprolol' => [['concor'], ['beta_blocker']],
        'carvedilol' => [['dilatrend'], ['beta_blocker', 'nonselective_beta_blocker']],
        'propranolol' => [['inderal'], ['beta_blocker', 'nonselective_beta_blocker']],
        'amiodarone' => [['cordarone'], []],
        'digoxin' => [['lanoxin'], []],
        'glyceryl trinitrate' => [['gtn', 'nitroglycerin'], ['nitrate']],
        'isosorbide' => [['imdur', 'isordil', 'isosorbide mononitrate', 'isosorbide dinitrate'], ['nitrate']],
        'sildenafil' => [['viagra'], ['pde5']],
        'tadalafil' => [['cialis'], ['pde5']],
        // Diabetes
        'metformin' => [['glucophage'], []],
        'gliclazide' => [['diamicron'], ['sulfonylurea']],
        'glibenclamide' => [['daonil'], ['sulfonylurea']],
        'glimepiride' => [['amaryl'], ['sulfonylurea']],
        'insulin' => [['mixtard', 'actrapid', 'lantus', 'novorapid', 'insulatard'], []],
        // Steroids / respiratory / cold
        'prednisolone' => [[], ['corticosteroid']],
        'prednisone' => [[], ['corticosteroid']],
        'dexamethasone' => [[], ['corticosteroid']],
        'methylprednisolone' => [['medrol'], ['corticosteroid']],
        'salbutamol' => [['ventolin', 'albuterol'], ['beta_agonist']],
        'pseudoephedrine' => [['sudafed', 'clarinase', 'actifed'], ['decongestant']],
        'phenylephrine' => [[], ['decongestant']],
        // Others
        'omeprazole' => [['losec'], ['ppi_cyp2c19']],
        'esomeprazole' => [['nexium'], ['ppi_cyp2c19']],
        'levothyroxine' => [['eltroxin', 'euthyrox', 'thyroxine'], []],
        'calcium carbonate' => [['calcium', 'caltrate'], ['mineral_binder']],
        'ferrous' => [['iron', 'ferrous sulfate', 'ferrous fumarate', 'obimin'], ['mineral_binder']],
        'antacid' => [['magnesium hydroxide', 'aluminium hydroxide', 'gaviscon', 'mylanta'], ['mineral_binder']],
        'allopurinol' => [['zyloprim'], []],
        'azathioprine' => [['imuran'], []],
        'methotrexate' => [[], []],
        'ethinylestradiol' => [['oral contraceptive', 'contraceptive pill', 'yasmin', 'diane-35', 'microgynon'], ['hormonal_contraceptive']],
    ];

    /** Words in an allergy entry that name a whole class rather than one drug. */
    private const ALLERGY_CLASS_WORDS = [
        'penicillin' => 'penicillin', 'penicillins' => 'penicillin', 'beta-lactam' => 'penicillin', 'beta lactam' => 'penicillin',
        'cephalosporin' => 'cephalosporin', 'cephalosporins' => 'cephalosporin',
        'sulfa' => 'sulfonamide', 'sulpha' => 'sulfonamide', 'sulfonamide' => 'sulfonamide', 'sulphonamide' => 'sulfonamide', 'sulfonamides' => 'sulfonamide',
        'nsaid' => 'nsaid', 'nsaids' => 'nsaid',
        'salicylate' => 'salicylate', 'salicylates' => 'salicylate',
        'macrolide' => 'macrolide', 'macrolides' => 'macrolide',
        'quinolone' => 'fluoroquinolone', 'quinolones' => 'fluoroquinolone', 'fluoroquinolone' => 'fluoroquinolone',
        'opioid' => 'opioid', 'opioids' => 'opioid', 'opiate' => 'opioid', 'opiates' => 'opioid',
        'statin' => 'statin', 'statins' => 'statin',
        'ace inhibitor' => 'ace_inhibitor', 'ace inhibitors' => 'ace_inhibitor',
        'benzodiazepine' => 'benzodiazepine', 'benzodiazepines' => 'benzodiazepine',
    ];

    /** Classes where an allergy to one member means the others should be avoided too. */
    private const CROSS_REACTIVE_CLASSES = ['penicillin', 'cephalosporin', 'sulfonamide', 'nsaid', 'macrolide', 'fluoroquinolone', 'opioid'];

    /** Taking two drugs from one of these classes at the same time is usually a duplication. */
    private const DUPLICATE_CLASSES = [
        'nsaid' => 'NSAIDs', 'statin' => 'statins', 'ace_inhibitor' => 'ACE inhibitors', 'arb' => 'ARBs',
        'ssri' => 'SSRI antidepressants', 'anticoagulant' => 'anticoagulants', 'beta_blocker' => 'beta-blockers',
        'sulfonylurea' => 'sulfonylureas', 'benzodiazepine' => 'benzodiazepines', 'opioid' => 'opioids',
        'corticosteroid' => 'oral corticosteroids', 'ccb' => 'calcium-channel blockers',
    ];

    private const CLASS_LABELS = [
        'penicillin' => 'a penicillin', 'cephalosporin' => 'a cephalosporin', 'sulfonamide' => 'a sulfonamide ("sulfa")',
        'nsaid' => 'an NSAID', 'salicylate' => 'a salicylate', 'macrolide' => 'a macrolide', 'fluoroquinolone' => 'a fluoroquinolone',
        'opioid' => 'an opioid', 'statin' => 'a statin', 'ace_inhibitor' => 'an ACE inhibitor', 'benzodiazepine' => 'a benzodiazepine',
    ];

    /**
     * Interaction rules. Each side is a list of "generic" or "class:<name>" tokens.
     * [sideA, sideB, severity, title, advice]
     */
    private const INTERACTIONS = [
        [['class:anticoagulant'], ['class:nsaid'], 'major', 'Bleeding risk', 'Anticoagulant with an NSAID/aspirin greatly increases bleeding risk (especially stomach bleeding). Prefer paracetamol for pain; if both are needed, confirm with the doctor and consider stomach protection.'],
        [['class:anticoagulant'], ['class:antiplatelet'], 'major', 'Bleeding risk', 'Anticoagulant with an antiplatelet raises bleeding risk. Only continue if the doctor intended dual therapy; watch for bruising, black stools or bleeding gums.'],
        [['warfarin'], ['clarithromycin', 'erythromycin', 'ciprofloxacin', 'levofloxacin', 'moxifloxacin', 'metronidazole', 'sulfamethoxazole', 'fluconazole', 'amiodarone'], 'major', 'Warfarin effect increased (INR rises)', 'This drug can raise the INR and cause bleeding. Check INR within a few days of starting and adjust the warfarin dose if needed.'],
        [['warfarin'], ['rifampicin'], 'major', 'Warfarin effect reduced', 'Rifampicin strongly lowers warfarin levels, so clots become more likely. The warfarin dose usually needs to go up, with close INR monitoring.'],
        [['simvastatin', 'lovastatin'], ['clarithromycin', 'erythromycin'], 'major', 'Muscle damage (rhabdomyolysis) risk', 'These antibiotics raise simvastatin/lovastatin levels a lot. Pause the statin during the antibiotic course, or use azithromycin instead.'],
        [['simvastatin', 'atorvastatin', 'lovastatin'], ['amiodarone', 'fluconazole'], 'moderate', 'Higher statin levels', 'Raises the risk of muscle pain or damage. Keep the statin dose low (e.g. simvastatin max 20 mg) and ask about muscle pain.'],
        [['class:pde5'], ['class:nitrate'], 'major', 'Dangerous drop in blood pressure', 'Sildenafil/tadalafil with nitrates can cause severe low blood pressure. Do not use together.'],
        [['class:ace_inhibitor', 'class:arb'], ['class:potassium_sparing', 'class:potassium', 'trimethoprim'], 'major', 'High potassium (hyperkalaemia)', 'Both raise blood potassium. Check potassium and kidney function, and avoid unless the doctor prescribed the combination on purpose.'],
        [['class:ace_inhibitor'], ['class:arb'], 'major', 'Double RAAS blockade', 'ACE inhibitor + ARB together raises the risk of kidney injury, high potassium and low blood pressure, with no extra benefit for most patients.'],
        [['class:ssri'], ['tramadol'], 'major', 'Serotonin syndrome and seizure risk', 'SSRIs with tramadol can cause serotonin syndrome (agitation, fever, shaking) and seizures. Choose another painkiller if possible.'],
        [['class:opioid'], ['class:benzodiazepine'], 'major', 'Breathing problems and heavy sedation', 'Opioids with benzodiazepines can slow breathing dangerously. Use the lowest doses and warn the patient not to drive or drink alcohol.'],
        [['methotrexate'], ['trimethoprim', 'sulfamethoxazole'], 'major', 'Methotrexate toxicity', 'Trimethoprim/co-trimoxazole raises methotrexate toxicity (blood count drop). Avoid; choose another antibiotic.'],
        [['methotrexate'], ['class:nsaid'], 'moderate', 'Methotrexate toxicity', 'NSAIDs reduce methotrexate clearance. Usually acceptable with low weekly doses, but monitor blood counts and kidney function.'],
        [['allopurinol'], ['azathioprine'], 'major', 'Azathioprine toxicity', 'Allopurinol blocks azathioprine breakdown and can cause severe bone-marrow suppression. The azathioprine dose must be greatly reduced or the combination avoided.'],
        [['lithium'], ['class:nsaid', 'class:ace_inhibitor', 'class:arb'], 'major', 'Lithium toxicity', 'This drug raises lithium levels. Check lithium levels and watch for tremor, confusion or vomiting.'],
        [['digoxin'], ['amiodarone', 'clarithromycin'], 'major', 'Digoxin toxicity', 'Raises digoxin levels. The digoxin dose usually needs to be reduced; monitor heart rate and digoxin level.'],
        [['rifampicin'], ['class:hormonal_contraceptive'], 'major', 'Contraceptive may fail', 'Rifampicin makes hormonal contraceptives ineffective. Advise a non-hormonal method during treatment and for 4 weeks after.'],
        [['clopidogrel'], ['class:ppi_cyp2c19'], 'moderate', 'Clopidogrel may work less well', 'Omeprazole/esomeprazole can reduce clopidogrel activation. Pantoprazole is a better choice if stomach protection is needed.'],
        [['class:nsaid'], ['class:ace_inhibitor', 'class:arb'], 'moderate', 'Kidney strain and weaker BP control', 'NSAIDs can reduce the blood-pressure effect and strain the kidneys, especially in older or dehydrated patients. Use short courses only.'],
        [['class:ssri'], ['class:nsaid', 'class:anticoagulant'], 'moderate', 'Bleeding risk', 'SSRIs add to the bleeding risk of NSAIDs and anticoagulants. Consider stomach protection and watch for bleeding.'],
        [['class:corticosteroid'], ['class:nsaid'], 'moderate', 'Stomach ulcer / bleeding risk', 'Steroids with NSAIDs increase the risk of stomach ulcers. Consider a PPI for protection.'],
        [['class:sulfonylurea'], ['fluconazole', 'clarithromycin', 'sulfamethoxazole'], 'moderate', 'Low blood sugar (hypoglycaemia)', 'This drug can raise sulfonylurea levels. Advise extra blood-sugar checks and teach hypo symptoms.'],
        [['class:nonselective_beta_blocker'], ['class:beta_agonist'], 'moderate', 'Inhaler may work less well', 'Non-selective beta-blockers (propranolol, carvedilol) can block salbutamol and worsen asthma. A cardio-selective beta-blocker (bisoprolol) is safer.'],
        [['levothyroxine'], ['class:mineral_binder'], 'moderate', 'Reduced absorption', 'Calcium, iron and antacids reduce levothyroxine absorption. Take levothyroxine at least 4 hours apart.'],
        [['ciprofloxacin', 'levofloxacin', 'moxifloxacin'], ['class:mineral_binder'], 'moderate', 'Antibiotic absorption reduced', 'Calcium, iron and antacids bind the antibiotic. Take the antibiotic 2 hours before or 6 hours after them.'],
        [['metformin', 'class:sulfonylurea', 'insulin'], ['class:corticosteroid'], 'moderate', 'Blood sugar may rise', 'Steroids raise blood sugar and can undo diabetes control. Advise extra glucose monitoring during the course.'],
    ];

    /**
     * Warnings for adding $newName to a patient who already takes $currentMeds.
     *
     * @param  iterable<\App\Models\Medication|string>  $currentMeds  other current medications (names or models)
     */
    public static function checkNew(string $newName, iterable $currentMeds, ?MedicalHistory $history): array
    {
        $new = self::resolve($newName);
        $warnings = [];

        $warnings = array_merge($warnings, self::allergyWarnings($newName, $new, $history));
        $warnings = array_merge($warnings, self::conditionWarnings($newName, $new, $history));

        foreach ($currentMeds as $med) {
            $otherName = is_string($med) ? $med : $med->name;
            $warnings = array_merge($warnings, self::pairWarnings($newName, $new, $otherName, self::resolve($otherName)));
        }

        return self::sortAndDedupe($warnings);
    }

    /** Every problem in a patient's current list (for the banner on the medications page). */
    public static function review(iterable $currentMeds, ?MedicalHistory $history): array
    {
        $meds = collect($currentMeds)->map(fn ($m) => is_string($m) ? $m : $m->name)->values();
        $resolved = $meds->map(fn ($name) => self::resolve($name));
        $warnings = [];

        foreach ($meds as $i => $name) {
            $warnings = array_merge($warnings, self::allergyWarnings($name, $resolved[$i], $history));
            $warnings = array_merge($warnings, self::conditionWarnings($name, $resolved[$i], $history));

            for ($j = $i + 1; $j < $meds->count(); $j++) {
                $warnings = array_merge($warnings, self::pairWarnings($name, $resolved[$i], $meds[$j], $resolved[$j]));
            }
        }

        return self::sortAndDedupe($warnings);
    }

    /** True when any warning is major. */
    public static function hasMajor(array $warnings): bool
    {
        return collect($warnings)->contains('severity', 'major');
    }

    /** The patient's drug allergy entries, ignoring "none", "NKDA" and similar. */
    public static function allergyEntries(?MedicalHistory $history): array
    {
        $text = strtolower(trim((string) ($history?->drug_allergies ?? '')));
        if ($text === '' || preg_match('/^(none|nil|no|nkda|nkfda|n\/a|na|-|tiada|tidak ada|no known( drug)? allerg(y|ies)( known)?)\.?$/', $text)) {
            return [];
        }

        $parts = preg_split('/[,;\/\n]+|\band\b|\bdan\b|&/', $text);

        return collect($parts)
            ->map(fn ($p) => trim(preg_replace('/\(.*?\)|\ballerg(y|ic|ies)\b|\bto\b|\bdrugs?\b|\bdrug\b|\bubat\b|[.:]/', ' ', $p)))
            ->map(fn ($p) => trim(preg_replace('/\s+/', ' ', $p)))
            ->filter(fn ($p) => strlen($p) >= 3 && ! in_array($p, ['none', 'nil', 'nkda', 'no known', 'tiada'], true))
            ->values()
            ->all();
    }

    // ---------------------------------------------------------------------

    /** Generics and classes recognised in a free-text medication name. */
    private static function resolve(string $name): array
    {
        $text = ' ' . strtolower($name) . ' ';
        $generics = [];
        $classes = [];

        foreach (self::DRUGS as $generic => [$aliases, $drugClasses]) {
            foreach (array_merge([$generic], $aliases) as $term) {
                if (preg_match('/(?<![a-z])' . preg_quote($term, '/') . '(?![a-z])/', $text)) {
                    $generics[] = $generic;
                    array_push($classes, ...$drugClasses);
                    break;
                }
            }
        }

        return ['generics' => array_values(array_unique($generics)), 'classes' => array_values(array_unique($classes))];
    }

    private static function allergyWarnings(string $name, array $drug, ?MedicalHistory $history): array
    {
        $warnings = [];

        foreach (self::allergyEntries($history) as $entry) {
            $allergenClass = self::ALLERGY_CLASS_WORDS[$entry] ?? null;
            $allergen = self::resolve($entry);

            // 1. Allergy to a whole class ("penicillin", "sulfa", "NSAIDs").
            if ($allergenClass && in_array($allergenClass, $drug['classes'], true)) {
                $warnings[] = self::warn('major', 'allergy', "Allergy: {$name}",
                    "Patient is recorded as allergic to \"{$entry}\", and {$name} is " . (self::CLASS_LABELS[$allergenClass] ?? $allergenClass) . '. Do not give unless the allergy has been ruled out.');
                continue;
            }

            // 2. Same drug (generic or brand).
            $same = array_intersect($allergen['generics'], $drug['generics']);
            if ($same) {
                $warnings[] = self::warn('major', 'allergy', "Allergy: {$name}",
                    "Patient is recorded as allergic to \"{$entry}\" (" . implode(', ', $same) . "), which is in {$name}. Do not give.");
                continue;
            }

            // 3. Different drug from the same allergy-relevant class (amoxicillin allergy → cloxacillin).
            $allergenClasses = $allergenClass ? [$allergenClass] : $allergen['classes'];
            $shared = array_values(array_intersect($allergenClasses, $drug['classes'], self::CROSS_REACTIVE_CLASSES));
            if ($shared) {
                $warnings[] = self::warn('major', 'allergy', "Possible cross-allergy: {$name}",
                    "Patient is allergic to \"{$entry}\". {$name} is also " . (self::CLASS_LABELS[$shared[0]] ?? $shared[0]) . ', so a reaction is likely. Choose a drug from another class.');
                continue;
            }

            // 4. Penicillin ↔ cephalosporin: small cross-reactivity.
            if ((in_array('penicillin', $allergenClasses, true) && in_array('cephalosporin', $drug['classes'], true))
                || (in_array('cephalosporin', $allergenClasses, true) && in_array('penicillin', $drug['classes'], true))) {
                $warnings[] = self::warn('moderate', 'allergy', "Cross-reactivity caution: {$name}",
                    "Patient is allergic to \"{$entry}\". A small share of penicillin-allergic patients also react to cephalosporins. Ask what the reaction was; avoid if it was severe (swelling, breathing problems, anaphylaxis).");
                continue;
            }

            // 5. Unknown drug: match the words themselves ("xyzmycin" allergy vs "Xyzmycin 250mg").
            if (! $allergen['generics'] && ! $allergenClass && strlen($entry) >= 4
                && preg_match('/(?<![a-z])' . preg_quote($entry, '/') . '(?![a-z])/', strtolower($name))) {
                $warnings[] = self::warn('major', 'allergy', "Allergy: {$name}",
                    "Patient is recorded as allergic to \"{$entry}\", which matches this medication name. Do not give.");
            }
        }

        return $warnings;
    }

    private static function conditionWarnings(string $name, array $drug, ?MedicalHistory $history): array
    {
        if (! $history) {
            return [];
        }

        $others = strtolower((string) $history->others);
        $hasHypertension = self::conditionSet($history->hypertension)
            || preg_match('/hypertension|high blood pressure|darah tinggi/', $others);
        $hasDiabetes = self::conditionSet($history->diabetes)
            || preg_match('/diabet|kencing manis/', $others);

        $warnings = [];
        if ($hasHypertension && in_array('nsaid', $drug['classes'], true) && ! self::isLowDoseAspirin($name, $drug)) {
            $warnings[] = self::warn('moderate', 'condition', "{$name} with hypertension",
                'NSAIDs can raise blood pressure and reduce the effect of BP medicines. Prefer paracetamol, or use the lowest dose for the shortest time and recheck BP.');
        }
        if ($hasHypertension && in_array('decongestant', $drug['classes'], true)) {
            $warnings[] = self::warn('moderate', 'condition', "{$name} with hypertension",
                'Decongestants such as pseudoephedrine can raise blood pressure. Suggest a saline spray or a non-decongestant cold remedy.');
        }
        if ($hasDiabetes && in_array('corticosteroid', $drug['classes'], true)) {
            $warnings[] = self::warn('moderate', 'condition', "{$name} with diabetes",
                'Steroids raise blood sugar. Advise more frequent glucose checks during the course.');
        }

        return $warnings;
    }

    private static function pairWarnings(string $nameA, array $a, string $nameB, array $b): array
    {
        $warnings = [];
        // Low-dose aspirin (e.g. Cardiprin 100) does not count as an NSAID for the kidney/BP rule.
        $lowDoseA = self::isLowDoseAspirin($nameA, $a);
        $lowDoseB = self::isLowDoseAspirin($nameB, $b);

        foreach (self::INTERACTIONS as [$side1, $side2, $severity, $title, $advice]) {
            $skipLowDose = $title === 'Kidney strain and weaker BP control';
            $tokensA = self::tokens($a, $skipLowDose && $lowDoseA);
            $tokensB = self::tokens($b, $skipLowDose && $lowDoseB);

            $hit = (array_intersect($side1, $tokensA) && array_intersect($side2, $tokensB))
                || (array_intersect($side1, $tokensB) && array_intersect($side2, $tokensA));
            if ($hit) {
                $warnings[] = self::warn($severity, 'interaction', "{$nameA} + {$nameB}: {$title}", $advice);
            }
        }

        // Same drug recorded twice (e.g. "Panadol" and "Paracetamol 500mg").
        $sameDrug = array_intersect($a['generics'], $b['generics']);
        if ($sameDrug) {
            $warnings[] = self::warn('moderate', 'duplicate', "{$nameA} + {$nameB}: same medicine twice",
                'Both contain ' . implode(', ', $sameDrug) . '. Check this is not a double dose (overdose risk) — stop one record if it was entered twice.');
        } else {
            foreach (array_intersect(array_keys(self::DUPLICATE_CLASSES), $a['classes'], $b['classes']) as $class) {
                $warnings[] = self::warn('moderate', 'duplicate', "{$nameA} + {$nameB}: two " . self::DUPLICATE_CLASSES[$class],
                    'Both belong to the same drug class. Taking two together usually adds side effects without extra benefit — confirm this is intended.');
            }
        }

        return $warnings;
    }

    private static function tokens(array $drug, bool $withoutNsaid = false): array
    {
        $classes = $withoutNsaid ? array_diff($drug['classes'], ['nsaid']) : $drug['classes'];

        return array_merge($drug['generics'], array_map(fn ($c) => "class:{$c}", $classes));
    }

    private static function conditionSet(?string $value): bool
    {
        $v = strtolower(trim((string) $value));

        return $v !== '' && ! in_array($v, ['none', 'no', 'nil', 'tiada', '-', 'n/a'], true);
    }

    /** Low-dose aspirin (≤ 150 mg, e.g. Cardiprin 100) has little effect on blood pressure. */
    private static function isLowDoseAspirin(string $name, array $drug): bool
    {
        return $drug['generics'] === ['aspirin']
            && (preg_match('/cardiprin/i', $name) || (preg_match('/(\d+)\s*mg/i', $name, $m) && (int) $m[1] <= 150));
    }

    private static function warn(string $severity, string $type, string $title, string $detail): array
    {
        return compact('severity', 'type', 'title', 'detail');
    }

    private static function sortAndDedupe(array $warnings): array
    {
        return collect($warnings)
            ->unique('title')
            ->sortBy(fn ($w) => [$w['severity'] === 'major' ? 0 : 1, $w['type'] === 'allergy' ? 0 : 1])
            ->values()
            ->all();
    }
}
