<?php
class GradeCalculator {
    private static $gradeRanges = [
        ['min' => 90, 'max' => 100, 'grade' => 'A', 'gpa' => 4.00, 'remarks' => 'Distinction'],
        ['min' => 80, 'max' => 89.99, 'grade' => 'A-', 'gpa' => 3.70, 'remarks' => 'Very Good'],
        ['min' => 70, 'max' => 79.99, 'grade' => 'B+', 'gpa' => 3.30, 'remarks' => 'First Division'],
        ['min' => 60, 'max' => 69.99, 'grade' => 'B', 'gpa' => 3.00, 'remarks' => 'Second Division'],
        ['min' => 50, 'max' => 59.99, 'grade' => 'B-', 'gpa' => 2.70, 'remarks' => 'Pass'],
        ['min' => 0, 'max' => 49.99, 'grade' => 'F', 'gpa' => 0, 'remarks' => 'Fail']
    ];

    public static function calculatePercentage($marks, $maxMarks) {
        return ($marks / $maxMarks) * 100;
    }

    public static function calculateGrade($percentage) {
        foreach (self::$gradeRanges as $range) {
            if ($percentage >= $range['min'] && $percentage <= $range['max']) {
                return [
                    'grade' => $range['grade'],
                    'gpa' => $range['gpa'],
                    'remarks' => $range['remarks']
                ];
            }
        }
        return null;
    }

    public static function calculateFinalGPA($subjectGPAs) {
        if (empty($subjectGPAs)) {
            return 0;
        }
        return array_sum($subjectGPAs) / count($subjectGPAs);
    }

    public static function getFinalRemarks($finalGPA) {
        if ($finalGPA >= 3.70) return 'Distinction';
        if ($finalGPA >= 3.30) return 'Very Good';
        if ($finalGPA >= 3.00) return 'First Division';
        if ($finalGPA >= 2.70) return 'Second Division';
        if ($finalGPA >= 2.00) return 'Pass';
        return 'Fail';
    }
}
?> 