<?php

namespace crmeb\services\ai\storage;

use crmeb\basic\BaseAi;

class Ai extends BaseAi
{
    const CONVERSATION = 'v2/chat/conversation';
    const CHAT = 'v2/chat/chat';

    protected function initialize(array $config = [])
    {
        parent::initialize($config);
    }

    public function chat(string $systemContent, string $userContent)
    {
        $param['messages'] = [
            [
                'role' => 'system',
                'content' => $systemContent
            ],
            [
                'role' => 'user',
                'content' => $userContent
            ]
        ];
        $param['max_tokens'] = '4096';
        $param['temperature'] = '1';
        $param['frequency_penalty'] = '0';
        $param['presence_penalty'] = '0';
        $param['model'] = 'deepseek-chat';
        $param['response_format'] = '{ "type": "json_object" }';
        $result = $this->accessToken->httpRequest(self::CHAT, $param);
        return str_replace(['```json', '```'], '', $result['data']['choices'][0]['message']['content']);
    }
}
