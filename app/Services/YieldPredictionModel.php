<?php

namespace App\Services;

use Illuminate\Support\Collection;

class YieldPredictionModel
{
    private array $coefficients = [];

    private float $intercept = 0;

    private float $rSquared = 0;

    private array $featureNames = [];

    /**
     * Train the multiple linear regression model on historical data.
     */
    public function train(Collection $historicalSchedules): void
    {
        if ($historicalSchedules->count() < 3) {
            return;
        }

        // Extract features and target variable
        $features = [];
        $targets = [];

        foreach ($historicalSchedules as $schedule) {
            $extractedFeatures = $this->extractFeatures($schedule);
            if ($extractedFeatures !== null) {
                $features[] = $extractedFeatures;
                $targets[] = $schedule->yield / max($schedule->hectares, 0.01); // Yield per hectare
            }
        }

        if (count($features) < 3) {
            return;
        }

        // Perform multiple linear regression
        $this->multipleLinearRegression($features, $targets);

        // Calculate R-squared
        $this->rSquared = $this->calculateRSquared($features, $targets);
    }

    /**
     * Predict yield per hectare based on features.
     */
    public function predict(array $features): float
    {
        if (empty($this->coefficients)) {
            return 0;
        }

        $prediction = $this->intercept;

        foreach ($this->coefficients as $index => $coefficient) {
            $prediction += $coefficient * ($features[$index] ?? 0);
        }

        return max(0, $prediction); // Ensure non-negative
    }

    /**
     * Get confidence score (0-1) based on R-squared and sample size.
     */
    public function getConfidence(int $sampleSize): float
    {
        if ($sampleSize < 3) {
            return 0;
        }

        // Adjust R-squared by sample size
        $adjustedRSquared = 1 - (1 - $this->rSquared) * (($sampleSize - 1) / max($sampleSize - count($this->coefficients) - 1, 1));

        return max(0, min(1, $adjustedRSquared));
    }

    /**
     * Get R-squared value.
     */
    public function getRSquared(): float
    {
        return $this->rSquared;
    }

    /**
     * Extract features from a schedule for prediction.
     */
    public function extractFeatures($schedule): ?array
    {
        // Load sensor readings if not already loaded
        if (! $schedule->relationLoaded('sensor')) {
            $schedule->load('sensor.readings');
        }

        // Calculate average soil moisture during growth period
        $avgMoisture = $this->calculateAverageMoisture($schedule);
        if ($avgMoisture === null) {
            return null;
        }

        // Calculate days from planting to harvest
        $daysToHarvest = $this->calculateDaysToHarvest($schedule);

        // Calculate seeds per hectare (planting density)
        $seedsPerHectare = $schedule->seeds_planted / max($schedule->hectares, 0.01);

        // Store feature names for reference
        $this->featureNames = [
            'avg_moisture',
            'days_to_harvest',
            'seeds_per_hectare',
            'hectares',
        ];

        return [
            $avgMoisture,
            $daysToHarvest,
            $seedsPerHectare,
            $schedule->hectares,
        ];
    }

    /**
     * Calculate average soil moisture during growth period.
     */
    private function calculateAverageMoisture($schedule): ?float
    {
        if (! $schedule->sensor || ! $schedule->sensor->readings) {
            return null;
        }

        $plantDate = $schedule->date_planted;
        $harvestDate = $schedule->actual_harvest_date ?? $schedule->expected_harvest_date;

        if (! $plantDate || ! $harvestDate) {
            return null;
        }

        $readings = $schedule->sensor->readings()
            ->whereBetween('created_at', [$plantDate, $harvestDate])
            ->get();

        if ($readings->isEmpty()) {
            // Fallback to latest reading or default
            return $schedule->sensor->latestReading->moisture ?? 50;
        }

        return $readings->avg('moisture');
    }

    /**
     * Calculate days from planting to harvest.
     */
    private function calculateDaysToHarvest($schedule): int
    {
        $plantDate = \Carbon\Carbon::parse($schedule->date_planted);
        $harvestDate = \Carbon\Carbon::parse(
            $schedule->actual_harvest_date ?? $schedule->expected_harvest_date
        );

        return max(1, $plantDate->diffInDays($harvestDate));
    }

    /**
     * Perform multiple linear regression using normal equations.
     * Y = β₀ + β₁X₁ + β₂X₂ + ... + βₙXₙ
     */
    private function multipleLinearRegression(array $X, array $y): void
    {
        $n = count($X);
        $m = count($X[0]);

        // Add intercept term (column of 1s)
        $X_with_intercept = [];
        foreach ($X as $row) {
            $X_with_intercept[] = array_merge([1], $row);
        }

        // Calculate β = (X'X)⁻¹X'y
        $XtX = $this->matrixMultiply($this->transpose($X_with_intercept), $X_with_intercept);
        $XtX_inv = $this->matrixInverse($XtX);
        $Xty = $this->matrixVectorMultiply($this->transpose($X_with_intercept), $y);
        $beta = $this->matrixVectorMultiply($XtX_inv, $Xty);

        // Extract intercept and coefficients
        $this->intercept = $beta[0];
        $this->coefficients = array_slice($beta, 1);
    }

    /**
     * Calculate R-squared (coefficient of determination).
     */
    private function calculateRSquared(array $X, array $y): float
    {
        $yMean = array_sum($y) / count($y);

        $ssTotal = 0;
        $ssResidual = 0;

        foreach ($X as $index => $features) {
            $yPred = $this->predict($features);
            $yActual = $y[$index];

            $ssTotal += pow($yActual - $yMean, 2);
            $ssResidual += pow($yActual - $yPred, 2);
        }

        if ($ssTotal == 0) {
            return 0;
        }

        return 1 - ($ssResidual / $ssTotal);
    }

    /**
     * Matrix multiplication (A × B).
     */
    private function matrixMultiply(array $A, array $B): array
    {
        $rowsA = count($A);
        $colsA = count($A[0]);
        $colsB = count($B[0]);

        $result = array_fill(0, $rowsA, array_fill(0, $colsB, 0));

        for ($i = 0; $i < $rowsA; $i++) {
            for ($j = 0; $j < $colsB; $j++) {
                for ($k = 0; $k < $colsA; $k++) {
                    $result[$i][$j] += $A[$i][$k] * $B[$k][$j];
                }
            }
        }

        return $result;
    }

    /**
     * Matrix-vector multiplication.
     */
    private function matrixVectorMultiply(array $A, array $v): array
    {
        $result = [];

        foreach ($A as $row) {
            $sum = 0;
            foreach ($row as $index => $value) {
                $sum += $value * $v[$index];
            }
            $result[] = $sum;
        }

        return $result;
    }

    /**
     * Transpose a matrix.
     */
    private function transpose(array $matrix): array
    {
        $rows = count($matrix);
        $cols = count($matrix[0]);

        $result = array_fill(0, $cols, array_fill(0, $rows, 0));

        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $result[$j][$i] = $matrix[$i][$j];
            }
        }

        return $result;
    }

    /**
     * Calculate matrix inverse using Gaussian elimination.
     * Simplified for small matrices (< 10x10).
     */
    private function matrixInverse(array $matrix): array
    {
        $n = count($matrix);

        // Create augmented matrix [A|I]
        $augmented = [];
        for ($i = 0; $i < $n; $i++) {
            $augmented[$i] = $matrix[$i];
            for ($j = 0; $j < $n; $j++) {
                $augmented[$i][] = ($i == $j) ? 1 : 0;
            }
        }

        // Gaussian elimination
        for ($i = 0; $i < $n; $i++) {
            // Find pivot
            $maxRow = $i;
            for ($k = $i + 1; $k < $n; $k++) {
                if (abs($augmented[$k][$i]) > abs($augmented[$maxRow][$i])) {
                    $maxRow = $k;
                }
            }

            // Swap rows
            $temp = $augmented[$i];
            $augmented[$i] = $augmented[$maxRow];
            $augmented[$maxRow] = $temp;

            // Make diagonal 1
            $pivot = $augmented[$i][$i];
            if (abs($pivot) < 1e-10) {
                // Singular matrix, return identity
                $identity = [];
                for ($ii = 0; $ii < $n; $ii++) {
                    $identity[$ii] = array_fill(0, $n, 0);
                    $identity[$ii][$ii] = 1;
                }

                return $identity;
            }

            for ($j = 0; $j < 2 * $n; $j++) {
                $augmented[$i][$j] /= $pivot;
            }

            // Eliminate column
            for ($k = 0; $k < $n; $k++) {
                if ($k != $i) {
                    $factor = $augmented[$k][$i];
                    for ($j = 0; $j < 2 * $n; $j++) {
                        $augmented[$k][$j] -= $factor * $augmented[$i][$j];
                    }
                }
            }
        }

        // Extract inverse from augmented matrix
        $inverse = [];
        for ($i = 0; $i < $n; $i++) {
            $inverse[$i] = array_slice($augmented[$i], $n);
        }

        return $inverse;
    }
}
