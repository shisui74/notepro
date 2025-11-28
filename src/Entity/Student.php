<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student extends User
{
    #[ORM\OneToMany(mappedBy: 'student', targetEntity: Grade::class, orphanRemoval: true)]
    private Collection $grades;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ClassLevel $classLevel = null;

    public function __construct()
    {
        parent::__construct();
        $this->grades = new ArrayCollection();
    }

    /**
     * @return Collection<int, Grade>
     */
    public function getGrades(): Collection
    {
        return $this->grades;
    }

    public function addGrade(Grade $grade): static
    {
        if (!$this->grades->contains($grade)) {
            $this->grades->add($grade);
            $grade->setStudent($this);
        }

        return $this;
    }

    public function removeGrade(Grade $grade): static
    {
        if ($this->grades->removeElement($grade)) {
            // set the owning side to null (unless already changed)
            if ($grade->getStudent() === $this) {
                $grade->setStudent(null);
            }
        }

        return $this;
    }

    public function getClassLevel(): ?ClassLevel
    {
        return $this->classLevel;
    }

    public function setClassLevel(?ClassLevel $classLevel): static
    {
        $this->classLevel = $classLevel;

        return $this;
    }

    public function getGradeByEval (Evaluation $evaluation): ?Grade
    {
        foreach ($this->getGrades() as $grade){
            if ($grade->getEvaluation() === $evaluation){
                return $grade;
            }
        }
        return null;
    }

    /**
     * Retourne un tableau structuré des notes par matière
     */
    public function getAverageBySubject(?Subject $subject = null): array|float
    {
        // Si on demande la moyenne pour un seul sujet, on retourne un float
        if ($subject !== null) {
            $scores = [];

            foreach ($this->getGrades() as $grade) {
                $evaluation = $grade->getEvaluation();
                if ($evaluation === null) {
                    continue;
                }

                // Si l'évaluation n'a pas de subject, on ignore
                if (!method_exists($evaluation, 'getSubject') || $evaluation->getSubject() !== $subject) {
                    continue;
                }

                $bareme = $evaluation->getBareme();
                $note = $grade->getGrade();

                if ($bareme == 0 || $note === null) {
                    continue;
                }

                // Normalise la note sur 20 (cast pour éviter les strings)
                $scores[] = ((float)$note / (float)$bareme) * 20.0;
            }

            if (count($scores) === 0) {
                return 0.0;
            }

            return array_sum($scores) / count($scores);
        }

        // Comportement original : tableau structuré par matière
        $resultat = [];

        foreach ($this->getGrades() as $grade) {
            $evaluation = $grade->getEvaluation();
            if ($evaluation === null) {
                continue;
            }

            // US2 : On vérifie si l'évaluation est publiée (si la méthode existe)
            if (method_exists($evaluation, 'estPubliee') && !$evaluation->estPubliee()) {
                continue;
            }

            $matiere = $evaluation->getSubject()->getLabel();

            if (!isset($resultat[$matiere])) {
                $resultat[$matiere] = [
                    'notes' => [],
                    'total_points' => 0,
                    'total_bareme' => 0,
                    'moyenne' => null
                ];
            }

            $resultat[$matiere]['notes'][] = $grade;

            if ((method_exists($grade, 'isPresent') ? $grade->isPresent() : true) && $grade->getGrade() !== null) {
                $resultat[$matiere]['total_points'] += (float)$grade->getGrade();
                $resultat[$matiere]['total_bareme'] += $evaluation->getBareme();
            }
        }

        foreach ($resultat as $matiere => $infos) {
            if ($infos['total_bareme'] > 0) {
                $moyenne = ($infos['total_points'] / $infos['total_bareme']) * 20;
                $resultat[$matiere]['moyenne'] = round($moyenne, 2);
            } else {
                $resultat[$matiere]['moyenne'] = 'N/A';
            }
        }

        return $resultat;
    }
}
