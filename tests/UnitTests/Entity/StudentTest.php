<?php


namespace App\Tests\UnitTests\Entity;

use App\Entity\Student;
use PHPUnit\Framework\TestCase;

class StudentTest extends TestCase
{
    public function testGetAverageBySubjectReturnsCorrectAverage(): void
    {
        $student = new Student();

        $subject = $this->createMock(\App\Entity\Subject::class);
        $otherSubject = $this->createMock(\App\Entity\Subject::class);

        $evaluation1 = $this->createMock(\App\Entity\Evaluation::class);
        $evaluation1->method('getSubject')->willReturn($subject);
        $evaluation1->method('getBareme')->willReturn(20);

        $evaluation2 = $this->createMock(\App\Entity\Evaluation::class);
        $evaluation2->method('getSubject')->willReturn($subject);
        $evaluation2->method('getBareme')->willReturn(40);

        $evaluationOther = $this->createMock(\App\Entity\Evaluation::class);
        $evaluationOther->method('getSubject')->willReturn($otherSubject);
        $evaluationOther->method('getBareme')->willReturn(20);

        $grade1 = $this->createMock(\App\Entity\Grade::class);
        $grade1->method('getEvaluation')->willReturn($evaluation1);
        $grade1->method('getGrade')->willReturn('15');

        $grade2 = $this->createMock(\App\Entity\Grade::class);
        $grade2->method('getEvaluation')->willReturn($evaluation2);
        $grade2->method('getGrade')->willReturn('10');

        $gradeOther = $this->createMock(\App\Entity\Grade::class);
        $gradeOther->method('getEvaluation')->willReturn($evaluationOther);
        $gradeOther->method('getGrade')->willReturn('18');

        $student->addGrade($grade1);
        $student->addGrade($grade2);
        $student->addGrade($gradeOther);

        $average = $student->getAverageBySubject($subject);

        // Attendu : ( (15/20)*20 + (10/40)*20 ) / 2 = (15 + 5) / 2 = 10
        $this->assertEqualsWithDelta(10.0, $average, 0.0001);
    }
}
