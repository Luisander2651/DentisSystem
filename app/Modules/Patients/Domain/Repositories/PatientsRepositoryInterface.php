<?php

interface PatientsContract
{
    public function register();

    public function updateProfile(): array;

    public function remove(): array;

    public function listActivePatients(): array;

    public function findById(): array;
}
