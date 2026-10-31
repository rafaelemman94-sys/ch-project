<?php
namespace App\Services;

enum LegislativeStream: string
{
    case APPROVED_RESOLUTIONS_OR_ORDINANCES = 'Approved Resolutions/Ordinances';
    case APPROVED_MINUTES = 'Approved Minutes';
    case MATTERS_FOR_REFERRAL = 'Matters for Referral';
    case CTFRB = 'CTFRB (Tricycle Franchises)';
    case SESSION_TRANSCRIPTS = 'Audio Recording / Session Transcripts';
}

enum LegislativeStatus: string
{
    case RECEIVING = 'Receiving';
    case REVIEW = 'Review';
    case AGENDA = 'Agenda';
    case APPROVAL = 'Approval';
    case AGENDA_SIGNING = 'Agenda Signing';
    case SP_SESSION = 'SP Session';
    case COMMITTEE_REFERRAL = 'Committee/Referral';
    case FINAL_DRAFT = 'Final Draft';
    case SIGNATURES = 'Signatures';
    case LCE_APPROVAL = 'LCE Approval';
    case FILING_SCAN = 'Filing/Scan';
    case ARCHIVE = 'Archive';
}

final class LegislativeWorkflow
{
    private const TRANSITIONS = [
        'Receiving' => ['review' => 'Review'],
        'Review' => ['agenda' => 'Agenda', 'revision' => 'Final Draft'],
        'Agenda' => ['revision' => 'Final Draft'],
        'Approval' => ['set_agenda' => 'Agenda Signing', 'revision' => 'Final Draft'],
        'Agenda Signing' => ['session' => 'SP Session'],
        'SP Session' => ['committee' => 'Committee/Referral', 'final_draft' => 'Final Draft'],
        'Committee/Referral' => ['final_draft' => 'Final Draft', 'revision' => 'Final Draft'],
        'Final Draft' => ['signature' => 'Signatures'],
        'Signatures' => ['lce_approval' => 'LCE Approval'],
        'LCE Approval' => ['filing' => 'Filing/Scan', 'revision' => 'Final Draft'],
        'Filing/Scan' => ['archive' => 'Archive'],
    ];

    private const LEGACY_STATUSES = [
        'Received' => 'Receiving',
        'Under Review' => 'Review',
        'For Agenda' => 'Agenda',
        'For Session' => 'SP Session',
        'Distribution' => 'Approval',
        'Committee' => 'Committee/Referral',
        'For Revision' => 'Final Draft',
        'For Approval' => 'Approval',
        'For Signature' => 'Signatures',
        'Signed' => 'LCE Approval',
        'Archived' => 'Archive',
    ];

    public static function initialStatus(string $stream): LegislativeStatus
    {
        LegislativeStream::from($stream);
        return LegislativeStatus::RECEIVING;
    }

    public static function transition(string $stream, string $current, string $action): LegislativeStatus
    {
        LegislativeStream::from($stream);
        $current = self::LEGACY_STATUSES[$current] ?? $current;
        LegislativeStatus::from($current);
        $next = self::TRANSITIONS[$current][$action] ?? null;
        if ($next === null) {
            throw new \InvalidArgumentException("Invalid workflow transition: {$stream}, {$current}, {$action}.");
        }
        return LegislativeStatus::from($next);
    }
}