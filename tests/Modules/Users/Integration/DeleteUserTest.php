<?php

declare(strict_types=1);

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

beforeEach(function () {
    $this->withoutRateLimiting();
    $this->admin = $this->actingAsAdmin();
});

it('deletes a staff member and removes the row', function () {
    // BR-6: the deletion is hard. `status = inactive` exists as the softer alternative,
    // but this endpoint removes the record outright.
    $user = $this->existingUser('Asistente');

    $this->deleteJson($this->userUrl($user->id))
        ->assertOk()
        ->assertJson(['message' => 'User deleted successfully']);

    expect(UserModel::query()->find($user->id))->toBeNull();
});

// BR-18: 404, where this used to answer 409 Conflict.
it('answers 404 for a user that does not exist', function () {
    $this->deleteJson($this->userUrl($this->missingUserId()))->assertNotFound();
});

// BR-19: this used to be a 500.
it('answers 400 for a malformed identifier', function () {
    $this->deleteJson($this->userUrl('not-a-uuid'))
        ->assertStatus(400)
        ->assertJsonPath('error', 'The provided user id is not valid.');
});

// BR-21: deleting your own account is the fastest route to locking the clinic out of its
// own admin panel, and there is no deactivated row left to restore afterwards.
it('refuses to let an administrator delete themselves', function () {
    $this->deleteJson($this->userUrl($this->admin->id))->assertForbidden();

    expect(UserModel::query()->find($this->admin->id))->not->toBeNull();
});

it('does not stop an administrator from deleting another administrator', function () {
    // The limit BR-21 deliberately does not cover, pinned so the decision stays visible.
    $other = $this->existingUser('Administrador');

    $this->deleteJson($this->userUrl($other->id))->assertOk();

    expect(UserModel::query()->find($other->id))->toBeNull();
});
