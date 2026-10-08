<?php

namespace App\Enums;

enum EventType: string
{
    case Scan = 'scan';
    case WhatsappClick = 'whatsapp_click';
    case CallClick = 'call_click';
    case SimilarClick = 'similar_click';
}
