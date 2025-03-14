<?php

namespace kissj\Participant;

use kissj\Event\Event;
use kissj\Orm\Repository;
use kissj\User\User;

class ParticipantRepository extends Repository
{
    /**
     * TODO optimalize
     *
     * @param string[] $roles
     * @param string[] $statuses
     * @param Event    $event
     * @param User     $adminUser
     * @return Participant[]
     */
    public function getAllParticipantsWithStatus(
        array $roles,
        array $statuses,
        Event $event,
        User $adminUser,
    ): array {
        /** @var Participant[] $participants */
        $participants = $this->findAll();
        $participants = $this->filterContingentAdminParticipants($participants, $adminUser);

        $validParticipants = [];
        foreach ($participants as $participant) {
            $user = $participant->getUserButNotNull();
            if (
                $user->event->id === $event->id
                && in_array($user->status, $statuses, true)
                && in_array($participant->role, $roles, true)
            ) {
                $validParticipants[$participant->id] = $participant;
            }
        }

        return $validParticipants;
    }

    /**
     * @param Participant[] $participants
     * @param User          $adminUser
     * @return Participant[]
     */
    private function filterContingentAdminParticipants(array $participants, User $adminUser): array
    {
        return match ($adminUser->role) {
            User::ROLE_ADMIN => $participants,
            User::ROLE_CONTINGENT_ADMIN_CS => array_filter($participants, function (Participant $p): bool {
                return $p->contingent === 'detail.contingent.czechia';
            }),
            User::ROLE_CONTINGENT_ADMIN_SK => array_filter($participants, function (Participant $p): bool {
                return $p->contingent === 'detail.contingent.slovakia';
            }),
            User::ROLE_CONTINGENT_ADMIN_PL => array_filter($participants, function (Participant $p): bool {
                return $p->contingent === 'detail.contingent.poland';
            }),
            User::ROLE_CONTINGENT_ADMIN_HU => array_filter($participants, function (Participant $p): bool {
                return $p->contingent === 'detail.contingent.hungary';
            }),
            User::ROLE_CONTINGENT_ADMIN_EU => array_filter($participants, function (Participant $p): bool {
                return $p->contingent === 'detail.contingent.european';
            }),
            default => [],
        };
    }
}
