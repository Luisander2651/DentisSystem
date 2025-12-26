<?php

interface UserContract
{
    public function register();

    public function updateProfile(): array;

    public function remove(): array;

    public function listActiveUsers(): array;

    public function findByRol(): array;

    public function findById(): array;
}
