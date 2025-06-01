<?php

declare(strict_types=1);

namespace Synchro\Violation\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Synchro\Violation\Enums\CSPLevel;
use Synchro\Violation\Enums\ReportSource;

/**
 * Declare properties.
 *
 * @property array $report
 * @property ReportSource $report_source
 * @property string $user_agent
 * @property string $ip
 * @property bool $forwarded
 * @property int $forward_attempts
 * @property string $created_at
 * @property string $updated_at
 */
class Violation extends Model
{
    use HasFactory;

    public $guarded = [];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'report',
        'report_source',
        'user_agent',
        'ip',
        'forwarded',
        'forward_attempts',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array<string,string>
     */
    protected $casts = [
        'report' => 'json',
        'report_source' => ReportSource::class,
    ];

    protected function table(): string
    {
        return config('violations.table');
    }

    #[Scope]
    protected function unforwarded(Builder $query): void
    {
        $maxAttempts = config('violations.max_forward_attempts', 3);
        $query->where('forwarded', false)
            ->where('forward_attempts', '<', $maxAttempts);
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
