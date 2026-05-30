<?php

namespace App\Services\Impl;

use App\Services\MessageService;

/**
 * Simple message formatter used by AutoreplyController to persist
 * reply data as JSON. No longer calls the Baileys Node.js server.
 */
class SimpleMessageService implements MessageService
{
    public function formatText($text, $footer = ''): array
    {
        return ['text' => $text, 'footer' => $footer];
    }

    public function formatLocation($latitude, $longitude): array
    {
        return ['location' => ['degreesLatitude' => $latitude, 'degreesLongitude' => $longitude]];
    }

    public function formatVcard($name, $phone): array
    {
        return ['contacts' => [['displayName' => $name, 'contacts' => [['vcard' => "BEGIN:VCARD\nFN:{$name}\nTEL:{$phone}\nEND:VCARD"]]]]];
    }

    public function formatImage($image, $caption = ''): array
    {
        return ['image' => ['url' => $image], 'caption' => $caption];
    }

    public function formatButtons($text, $buttons, $urlimage = '', $footer = ''): array
    {
        return ['text' => $text, 'buttons' => $buttons, 'image' => $urlimage, 'footer' => $footer];
    }

    public function formatTemplates($text, $buttons, $urlimage = '', $footer = ''): array
    {
        return ['text' => $text, 'templateButtons' => $buttons, 'image' => $urlimage, 'footer' => $footer];
    }

    public function formatLists($text, $lists, $title, $name, $buttonText, $footer = ''): array
    {
        return ['text' => $text, 'sections' => $lists, 'title' => $title, 'buttonText' => $buttonText, 'footer' => $footer];
    }

    public function format($type, $data): array
    {
        return match ($type) {
            'text'     => $this->formatText($data->message ?? '', $data->footer ?? ''),
            'image'    => $this->formatImage($data->url ?? $data->image ?? '', $data->caption ?? $data->message ?? ''),
            'location' => $this->formatLocation($data->latitude ?? 0, $data->longitude ?? 0),
            'vcard'    => $this->formatVcard($data->name ?? '', $data->phone ?? ''),
            'button'   => $this->formatButtons($data->message ?? '', (array) ($data->button ?? []), $data->image ?? '', $data->footer ?? ''),
            'template' => $this->formatTemplates($data->message ?? '', (array) ($data->template ?? []), $data->image ?? '', $data->footer ?? ''),
            'list'     => $this->formatLists($data->message ?? '', (array) ($data->list ?? []), $data->title ?? '', $data->name ?? '', $data->buttontext ?? '', $data->footer ?? ''),
            'media'    => ['type' => $data->media_type ?? 'image', 'url' => $data->url ?? '', 'caption' => $data->caption ?? ''],
            'sticker'  => ['type' => 'sticker', 'url' => $data->url ?? ''],
            'poll'     => ['name' => $data->name ?? '', 'option' => (array) ($data->option ?? [])],
            default    => ['message' => $data->message ?? ''],
        };
    }
}
