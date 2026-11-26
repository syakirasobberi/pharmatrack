<?php

namespace App\Support;

/**
 * The wording the patient agrees to before their face is registered (PDPA 2010:
 * biometric data is sensitive personal data and needs explicit consent).
 * Shown to the pharmacist at capture and to the patient in their portal, so both see the same text.
 */
class FaceConsent
{
    public const STATEMENT = 'The patient agrees that PharmaTrack stores a face template (numbers, not a photo) '
        . 'so the kiosk and Quick Scan can recognise them. It is used only to open their own health record at this pharmacy, '
        . 'is never shared, and they can withdraw at any time — the face data is then deleted.';

    public const PATIENT_SUMMARY = 'PharmaTrack keeps a face template (numbers, not a photo) so the kiosk and your pharmacist '
        . 'can recognise you. It is used only to open your own record here and is never shared.';
}
