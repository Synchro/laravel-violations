<?php

namespace Synchro\Violation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Synchro\Violation\Enums\CSPLevel;
use Synchro\Violation\Enums\ReportType;

/**
 * Declare properties.
 *
 * @property array $report
 * @property ReportType $report_type
 * @property string $user_agent
 * @property string $ip
 * @property string $created_at
 * @property string $updated_at
 */
class Violation extends Model
{
    use HasFactory;

    public $guarded = [];

    protected $fillable = [
        'report',
        'report_type',
        'user_agent',
        'ip',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'report' => 'json',
        'report_type' => ReportType::class,
    ];

    protected function table(): string
    {
        return config('violations.table_name');
    }

    public function scopeUnforwarded(Builder $query): void
    {
        $query->where('forwarded', false);
    }

    /**
     * Given a report, try to figure out what kind of report it is.
     */
    public function detectCSPReportLevel(array $report): CSPLevel
    {
        // Level 3-specific fields
        $level3Fields = [
            'disposition',
            'input-type',
            'input-attribution',
            'element-attribution',
        ];

        // Check for Level 3-specific fields
        foreach ($level3Fields as $field) {
            if (array_key_exists($field, $report['csp-report'])) {
                return CSPLevel::LEVEL3;
            }
        }

        // Check for granular directives (Level 3 feature)
        if (array_key_exists('violated-directive', $report['csp-report'])) {
            $granularDirectives = ['script-src-elem', 'style-src-attr', 'script-src-attr', 'style-src-elem'];
            foreach ($granularDirectives as $directive) {
                if (strpos($report['csp-report']['violated-directive'], $directive) !== false) {
                    return CSPLevel::LEVEL3;
                }
            }
        }

        // If we can't definitively say it's Level 3, assume it's Level 2
        return CSPLevel::LEVEL2;
    }
}
