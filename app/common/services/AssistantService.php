<?php
declare(strict_types=1);

namespace common\services;

use common\repositories\AssistantRepository;

class AssistantService
{
    public function __construct(
        public AssistantRepository $assistantRepository,
    ){ }

    public function getAllAssistants(): array
    {
        return $this->assistantRepository->getAll();
    }


}