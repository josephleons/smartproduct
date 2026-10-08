<?php

/*
| Swahili validation messages. Rules not listed here fall back to English
| (fallback_locale), so only rules the app actually uses need a line.
*/

return [

    'accepted' => 'Sehemu ya :attribute lazima ikubaliwe.',
    'array' => 'Sehemu ya :attribute lazima iwe orodha.',
    'boolean' => 'Sehemu ya :attribute lazima iwe ndiyo au hapana.',
    'confirmed' => 'Uthibitisho wa :attribute haulingani.',
    'current_password' => 'Nenosiri si sahihi.',
    'date' => 'Sehemu ya :attribute lazima iwe tarehe sahihi.',
    'email' => 'Sehemu ya :attribute lazima iwe barua pepe sahihi.',
    'enum' => ':attribute uliyochagua si sahihi.',
    'exists' => ':attribute uliyochagua si sahihi.',
    'file' => 'Sehemu ya :attribute lazima iwe faili.',
    'image' => 'Sehemu ya :attribute lazima iwe picha.',
    'in' => ':attribute uliyochagua si sahihi.',
    'integer' => 'Sehemu ya :attribute lazima iwe namba kamili.',
    'max' => [
        'array' => 'Sehemu ya :attribute isizidi vitu :max.',
        'file' => 'Sehemu ya :attribute isizidi kilobaiti :max.',
        'numeric' => 'Sehemu ya :attribute isizidi :max.',
        'string' => 'Sehemu ya :attribute isizidi herufi :max.',
    ],
    'mimes' => 'Sehemu ya :attribute lazima iwe faili ya aina: :values.',
    'min' => [
        'array' => 'Sehemu ya :attribute lazima iwe na angalau vitu :min.',
        'file' => 'Sehemu ya :attribute lazima iwe angalau kilobaiti :min.',
        'numeric' => 'Sehemu ya :attribute lazima iwe angalau :min.',
        'string' => 'Sehemu ya :attribute lazima iwe na angalau herufi :min.',
    ],
    'numeric' => 'Sehemu ya :attribute lazima iwe namba.',
    'password' => [
        'letters' => 'Sehemu ya :attribute lazima iwe na angalau herufi moja.',
        'mixed' => 'Sehemu ya :attribute lazima iwe na angalau herufi kubwa moja na ndogo moja.',
        'numbers' => 'Sehemu ya :attribute lazima iwe na angalau namba moja.',
        'symbols' => 'Sehemu ya :attribute lazima iwe na angalau alama moja.',
        'uncompromised' => ':attribute hii imeonekana kwenye uvujaji wa data. Tafadhali chagua nyingine.',
    ],
    'regex' => 'Muundo wa :attribute si sahihi.',
    'required' => 'Sehemu ya :attribute ni lazima.',
    'required_if' => 'Sehemu ya :attribute ni lazima wakati :other ni :value.',
    'string' => 'Sehemu ya :attribute lazima iwe maandishi.',
    'unique' => ':attribute hii tayari imetumika.',
    'uploaded' => 'Imeshindikana kupakia :attribute.',
    'url' => 'Sehemu ya :attribute lazima iwe kiungo sahihi.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'attributes' => [
        'address' => 'anwani',
        'amount' => 'kiasi',
        'brand' => 'chapa',
        'condition' => 'hali',
        'copies' => 'nakala',
        'description' => 'maelezo',
        'email' => 'barua pepe',
        'grace_days' => 'siku za nyongeza',
        'location' => 'mahali',
        'logo' => 'nembo',
        'model' => 'modeli',
        'monthly_price' => 'bei ya mwezi',
        'name' => 'jina',
        'note' => 'maelezo',
        'password' => 'nenosiri',
        'phone' => 'simu',
        'price' => 'bei',
        'products' => 'bidhaa',
        'quantity' => 'idadi',
        'reference' => 'namba ya muamala',
        'role' => 'wadhifa',
        'serial_number' => 'namba ya siri',
        'status' => 'hali',
        'template' => 'kiolezo',
        'trial_days' => 'siku za majaribio',
        'warranty_months' => 'miezi ya dhamana',
    ],

];
