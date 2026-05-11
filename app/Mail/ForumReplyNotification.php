<?php

namespace App\Mail;

use App\Models\ForumPost;
use App\Models\ForumTopic;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForumReplyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ForumTopic $topic,
        public ForumPost  $post,
        public string     $topicUrl,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('💬 Nouvelle réponse sur votre sujet — ' . $this->topic->title)
            ->view('emails.forum.reply');
    }
}
