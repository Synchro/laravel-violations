<?php

declare(strict_types=1);

namespace Synchro\Violation\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Synchro\Violation\Enums\ReportSource;

/**
 * Declare properties.
 *
 * @property int $id
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
}
