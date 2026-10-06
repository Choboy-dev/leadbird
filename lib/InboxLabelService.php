<?php

declare(strict_types=1);

final class InboxLabelService
{
    public function ensureDefaults(string $businessId): void
    {
        RepositoryFactory::inboxLabels()->ensureFavoritesLabel($businessId);
    }

    /** @return array{ok: bool, message?: string, label?: array<string, mixed>} */
    public function createLabel(string $businessId, string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['ok' => false, 'message' => 'Enter a label name.'];
        }

        if (strlen($name) > 100) {
            return ['ok' => false, 'message' => 'Label name is too long.'];
        }

        $slug = $this->slugForName($name);
        $repo = RepositoryFactory::inboxLabels();

        if ($repo->findByBusinessAndSlug($businessId, $slug) !== null) {
            return ['ok' => false, 'message' => 'A label with that name already exists.'];
        }

        if ($slug === InboxLabelRepository::FAVORITES_SLUG) {
            return ['ok' => false, 'message' => 'That label name is reserved.'];
        }

        $label = $repo->create($businessId, $name, $slug);

        return ['ok' => true, 'label' => $this->formatLabel($label, 0)];
    }

    /** @return array{ok: bool, message?: string} */
    public function deleteLabel(string $businessId, string $labelId): array
    {
        $label = RepositoryFactory::inboxLabels()->findById($labelId);
        if ($label === null || (string) ($label['business_id'] ?? '') !== $businessId) {
            return ['ok' => false, 'message' => 'Label not found.'];
        }

        if ((int) ($label['is_system'] ?? 0) === 1) {
            return ['ok' => false, 'message' => 'System labels cannot be deleted.'];
        }

        RepositoryFactory::inboxLabels()->delete($labelId, $businessId);

        return ['ok' => true];
    }

    /**
     * @return array{
     *     ok: bool,
     *     message?: string,
     *     assigned?: bool,
     *     label_ids?: list<string>
     * }
     */
    public function toggleAssignment(string $businessId, string $conversationId, string $labelId): array
    {
        $conversation = RepositoryFactory::conversations()->findById($conversationId);
        if ($conversation === null || (string) ($conversation['business_id'] ?? '') !== $businessId) {
            return ['ok' => false, 'message' => 'Conversation not found.'];
        }

        $label = RepositoryFactory::inboxLabels()->findById($labelId);
        if ($label === null || (string) ($label['business_id'] ?? '') !== $businessId) {
            return ['ok' => false, 'message' => 'Label not found.'];
        }

        $repo = RepositoryFactory::inboxLabels();
        $assigned = $repo->isAssigned($conversationId, $labelId);
        if ($assigned) {
            $repo->unassign($conversationId, $labelId);
        } else {
            $repo->assign($conversationId, $labelId);
        }

        return [
            'ok' => true,
            'assigned' => !$assigned,
            'label_ids' => $repo->listLabelIdsForConversation($conversationId),
        ];
    }

    /** @return array<string, mixed> */
    public function formatLabel(array $label, int $chatCount): array
    {
        return [
            'id' => (string) ($label['id'] ?? ''),
            'name' => (string) ($label['name'] ?? ''),
            'slug' => (string) ($label['slug'] ?? ''),
            'is_system' => (int) ($label['is_system'] ?? 0) === 1,
            'chat_count' => $chatCount,
        ];
    }

    private function slugForName(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'label';
    }
}
