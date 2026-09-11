<?php

return [
    'accepted' => ':attribute kabul edilmelidir.',
    'after' => ':attribute, :date tarihinden sonra olmalıdır.',
    'boolean' => ':attribute alanı doğru/yanlış olmalıdır.',
    'after_or_equal' => ':attribute, :date tarihinden sonra veya aynı gün olmalıdır.',
    'before' => ':attribute, :date tarihinden önce olmalıdır.',
    'before_or_equal' => ':attribute, :date tarihinden önce veya aynı gün olmalıdır.',
    'between' => [
        'numeric' => ':attribute :min ile :max arasında olmalıdır.',
        'string' => ':attribute :min ile :max karakter arasında olmalıdır.',
    ],
    'date' => ':attribute geçerli bir tarih olmalıdır.',
    'digits' => ':attribute :digits haneli olmalıdır.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'exists' => 'Seçilen :attribute geçersiz.',
    'in' => 'Seçilen :attribute geçersiz.',
    'integer' => ':attribute tam sayı olmalıdır.',
    'lte' => [
        'numeric' => ':attribute en fazla :value olabilir.',
    ],
    'max' => [
        'numeric' => ':attribute en fazla :max olabilir.',
        'string' => ':attribute en fazla :max karakter olabilir.',
    ],
    'min' => [
        'numeric' => ':attribute en az :min olmalıdır.',
        'string' => ':attribute en az :min karakter olmalıdır.',
    ],
    'numeric' => ':attribute sayısal bir değer olmalıdır.',
    'regex' => ':attribute biçimi geçersiz.',
    'required' => ':attribute alanı zorunludur.',
    'required_with' => ':attribute alanı zorunludur.',
    'confirmed' => ':attribute tekrarı uyuşmuyor.',
    'current_password' => 'Şifre yanlış.',
    'string' => ':attribute metin olmalıdır.',
    'unique' => 'Bu :attribute zaten kayıtlı.',

    'attributes' => [
        'email' => 'e-posta',
        'login' => 'kullanıcı adı / e-posta',
        'password' => 'şifre',
        'content' => 'not',
        'year' => 'yıl',
        'month' => 'ay',
    ],
];
