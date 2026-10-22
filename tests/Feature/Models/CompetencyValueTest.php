<?php

use App\Models\Competency;

describe('compétence binaire', function () {
    beforeEach(function () {
        $this->competency = Competency::factory()->create();
    });

    it('accepte uniquement 0 et 1', function () {
        expect($this->competency->acceptsValue(0))->toBeTrue()
            ->and($this->competency->acceptsValue(1))->toBeTrue()
            ->and($this->competency->acceptsValue(2))->toBeFalse()
            ->and($this->competency->acceptsValue(-1))->toBeFalse();
    });

    it('est acquise avec la valeur 1', function () {
        expect($this->competency->isAcquiredWith(1))->toBeTrue()
            ->and($this->competency->isAcquiredWith(0))->toBeFalse()
            ->and($this->competency->isAcquiredWith(null))->toBeFalse();
    });
});

describe('compétence sur une échelle', function () {
    beforeEach(function () {
        $this->competency = Competency::factory()
            ->scale([0 => 'Non acquis', 1 => 'En cours', 2 => 'Acquis', 3 => 'Expert'], threshold: 2)
            ->create();
    });

    it('accepte uniquement les valeurs de ses niveaux', function () {
        expect($this->competency->acceptsValue(0))->toBeTrue()
            ->and($this->competency->acceptsValue(3))->toBeTrue()
            ->and($this->competency->acceptsValue(4))->toBeFalse();
    });

    it('est acquise à partir du seuil', function () {
        expect($this->competency->isAcquiredWith(1))->toBeFalse()
            ->and($this->competency->isAcquiredWith(2))->toBeTrue()
            ->and($this->competency->isAcquiredWith(3))->toBeTrue()
            ->and($this->competency->isAcquiredWith(null))->toBeFalse();
    });
});
