<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public WorkspaceInvitation $invitation
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $invitation = $this->invitation;
        $workspace = $invitation->workspace;
        $inviter = $invitation->inviter;

        $acceptUrl = route('workspace.invitation.accept', [
            'token' => $invitation->token,
        ]);

        return (new MailMessage)
            ->subject("You've been invited to join {$workspace->name}")
            ->greeting('Hello!')
            ->line("{$inviter->name} has invited you to join the {$workspace->name} workspace as a {$invitation->role}.")
            ->line('You can accept this invitation by clicking the button below:')
            ->action('Accept Invitation', $acceptUrl)
            ->line("This invitation will expire on {$invitation->expires_at->format('M j, Y')}.")
            ->line('If you did not expect to receive this invitation, you can safely ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'workspace_id' => $this->invitation->workspace_id,
            'workspace_name' => $this->invitation->workspace->name,
            'inviter_name' => $this->invitation->inviter->name,
            'role' => $this->invitation->role,
        ];
    }
}
