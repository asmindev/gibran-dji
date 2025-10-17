<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AprioriAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'rules',
        'confidence',
        'support',
        'transaction_date',
        'description'
    ];

    protected $casts = [
        'rules' => 'array',
        'confidence' => 'decimal:2',
        'support' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    /**
     * Get first item (antecedent) from rules array
     */
    public function getAntecedentAttribute()
    {
        return $this->rules[0] ?? null;
    }

    /**
     * Get second item (consequent) from rules array
     */
    public function getConsequentAttribute()
    {
        return $this->rules[1] ?? null;
    }

    /**
     * Save analysis results to database (satu per satu association rule)
     */
    public static function saveAnalysis($algorithmSteps, $selectedDate, $minSupport, $minConfidence, $sampleTransactions, $createdBy = null)
    {
        // Cek apakah association rules tidak kosong
        if (empty($algorithmSteps['association_rules']) || count($algorithmSteps['association_rules']) === 0) {
            return null; // Tidak simpan jika association rules kosong
        }

        $savedCount = 0;

        // Simpan setiap association rule sebagai record terpisah
        foreach ($algorithmSteps['association_rules'] as $rule) {
            // Parse rule untuk mendapatkan antecedent dan consequent
            $ruleItems = explode(' → ', $rule['rule']);
            $antecedent = trim($ruleItems[0]);
            $consequent = trim($ruleItems[1]);

            $saved = self::create([
                'rules' => [$antecedent, $consequent], // Array format: ["Jersey Mills", "Sepatu Bola Ortus"]
                'confidence' => $rule['confidence'],
                'support' => $rule['support'],
                'transaction_date' => Carbon::parse($selectedDate === 'all' ? Carbon::today() : $selectedDate),
                'description' => "Jika membeli {$antecedent} maka membeli {$consequent}"
            ]);

            if ($saved) {
                $savedCount++;
            }
        }

        return $savedCount; // Return jumlah rules yang berhasil disimpan
    }
}
