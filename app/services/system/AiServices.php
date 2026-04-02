<?php

namespace app\services\system;

use app\services\BaseServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\serve\ServeServices;

class AiServices extends BaseServices
{
    /**
     * @var ServeServices
     */
    protected ServeServices $services;

    public function __construct(ServeServices $serveServices)
    {
        $this->services = $serveServices;
    }

    public function AiType($data)
    {
        switch ($data['type']) {
            case 'product_name':
                $result = $this->productName($data);
                break;
            case 'product_info':
                $result = $this->productInfo($data);
                break;
            case 'share_content':
                $result = $this->shareContent($data);
                break;
            case 'product_attr':
                $result = $this->productAttr($data);
                break;
            case 'product_specs':
                $result = $this->productSpecs($data);
                break;
            case 'product_reply':
                $result = $this->productReply($data);
                break;
            case 'community':
                $result = $this->community($data);
                break;
            case 'community_title':
                $result = $this->communityTitle($data);
                break;
            case 'community_content':
                $result = $this->communityContent($data);
                break;
            case 'article_title':
                $result = $this->articleTitle($data);
                break;
            case 'article_content':
                $result = $this->articleContent($data);
                break;
            default:
                return '暂不支持该类型';
        }
        return $result;
    }

    public function productName($data)
    {
        $systemContent = ' 
请严格按此规范执行：  
1. 核心指令：  
   • 生成3条差异化标题，每条30-50字，符合电商平台SEO规则  
   • 必须包含：核心关键词（前10字）+核心卖点（材质/功能）+附加价值（促销/场景/人群）  
   • 禁止：重复词、违禁词、模糊描述（如“优质”“多功能”）  
2. 优化维度：  
   ▶ 标题1（流量型）：关键词堆砌+高频搜索词，例：  
   “2024新款男士运动鞋透气防滑跑步鞋轻便百搭男鞋网面休闲鞋耐磨减震”  
   ▶ 标题2（转化型）：强促销+紧迫感，例：  
   “【限时5折】男士运动鞋春秋季飞织透气跑步鞋学生潮流男鞋次日达退货运费险”  
   ▶ 标题3（场景型）：痛点解决方案，例：  
   “健身房必备！男士爆款运动鞋防滑减震跑步鞋宽脚掌透气训练鞋久穿不累”  
3. 输出格式：仅返回JSON，键名固定为t1/t2/t3（节省字符）：  
{  
  "t1": "标题1（关键词+功能）",  
  "t2": "标题2（促销+服务）",  
  "t3": "标题3（场景+痛点）"  
}  
4. 特殊要求：  
   • 若标题含促销，需标注具体力度（如“7折”而非“促销中”）  
   • 人群定向需明确（如“中老年”“宝妈”而非“通用”）  
   • 材质/规格必须具体（如“新疆棉”“20000mAh”）
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return array_values(json_decode($result, true));
    }

    public function productInfo($data)
    {
        $systemContent = ' 
请严格按此规范执行：  
1. 核心指令：  
   • 生成3条差异化商品简介，每条不少于80字，符合电商平台SEO规则  
   • 必须包含：核心关键词（前10字）+核心卖点（材质/功能）+附加价值（促销/场景/人群）  
   • 禁止：重复词、违禁词、模糊描述（如“优质”“多功能”）  
2. 优化维度：  
   ▶ 标题1（流量型）：关键词堆砌+高频搜索词，例：  
   “2024新款男士运动鞋透气防滑跑步鞋轻便百搭男鞋网面休闲鞋耐磨减震”  
   ▶ 标题2（转化型）：强促销+紧迫感，例：  
   “【限时5折】男士运动鞋春秋季飞织透气跑步鞋学生潮流男鞋次日达退货运费险”  
   ▶ 标题3（场景型）：痛点解决方案，例：  
   “健身房必备！男士爆款运动鞋防滑减震跑步鞋宽脚掌透气训练鞋久穿不累”  
3. 输出格式：仅返回JSON，键名固定为t1/t2/t3（节省字符）：  
{  
  "t1": "标题1（关键词+功能）",  
  "t2": "标题2（促销+服务）",  
  "t3": "标题3（场景+痛点）"  
}  
4. 特殊要求：  
   • 若标题含促销，需标注具体力度（如“7折”而非“促销中”）  
   • 人群定向需明确（如“中老年”“宝妈”而非“通用”）  
   • 材质/规格必须具体（如“新疆棉”“20000mAh”）
';
        $userContent = $data['store_name'] . $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return array_values(json_decode($result, true));
    }

    public function shareContent($data)
    {
        $systemContent = ' 
请严格按此规范执行： 
1. 核心指令：  
   • 生成3条差异化推广文案，每条不少于100字，符合各平台投放规范  
   • 必须包含：核心卖点（前15字）+用户痛点+行动号召  
   • 禁止：虚假宣传、绝对化用语（如"最佳""第一"）、模糊表述  
2. 优化维度：  
   ▶ 文案1（痛点刺激型）：直击用户痛点+解决方案，例：  
   "跑步膝盖疼？新一代缓震科技运动鞋，采用XX减震材料，降低关节冲击力30%，立即体验→"  
   ▶ 文案2（场景共鸣型）：构建使用场景+情感共鸣，例：  
   "加班到深夜的你值得更好！人体工学办公椅，8小时久坐不腰酸，今日下单享免费安装→"  
   ▶ 文案3（数据实证型）：量化效果+权威背书，例：  
   "92%用户回购的爆款面膜！中科院认证XX成分，实测28天淡化细纹45%，限时买二送一→"  
3. 输出格式：仅返回JSON，键名固定为c1/c2/c3：  
{  
  "c1": "文案1（痛点型）",  
  "c2": "文案2（场景型）",  
  "c3": "文案3（数据型）"  
}  
4. 特殊要求：  
   • 痛点描述需具体（如"久坐腰酸"而非"不舒服"）  
   • 数据必须真实可查（如"实验室实测""用户调研数据"）  
   • 行动号召明确（如"立即咨询""限时特惠"需标注截止时间）
';
        $userContent = $data['store_name'] . $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return array_values(json_decode($result, true));
    }

    public function productAttr($data)
    {
        $systemContent = ' 
请严格按此规范执行：
1. 核心指令：
• 自动识别商品类目并生成完整规格参数
• 参数值必须为具体可选项（非范围值）
• 包含：基础参数+技术参数+功能参数+扩展参数
• 最多三个规格类型，每个规格最多10个值
2. 输出格式：仅返回JSON，键名固定为value/detail：
{
  "rule_name": "商品类目"
  "spec": [
    {
      "value": "规格名称1",
      "detail": [
        "规格值1-1", 
        "规格值1-2"
      ]
    },
    {
      "value": "规格名称2",
      "detail": [
        "规格值2-1", 
        "规格值2-2"
      ]
    },
    ...
  ]
}
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return json_decode($result, true);
    }

    public function productSpecs($data)
    {
        $systemContent = ' 
请严格按此规范执行：
1. 核心指令：
• 自动识别商品类目并生成完整规格参数
• 参数值必须为具体可选项（非范围值）
• 包含：基础参数+技术参数+功能参数+扩展参数
• 生成最少5个specs参数，name和value都是字符串,sort为0
2. 输出格式：仅返回JSON：
{
  "name": "参数模板名称",
  "specs": [
    {
      "name": "参数名称1",
      "value": "参数值1",
      "sort": 0,
    },
    {
      "name": "参数名称2",
      "value": "参数值2",
      "sort": 0,
    }
    ...
  ]
}
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return json_decode($result, true);
    }

    public function productReply($data)
    {
        $productInfo = app()->make(StoreProductServices::class)->get($data['product_id']);
        $sku = app()->make(StoreProductAttrValueServices::class)->value(['product_id' => $data['product_id'], 'unique' => $data['unique']], 'suk');
        $date = date('Y-m-d H:i:s');
        $systemContent = ' 
请严格按此规范执行：
1. 核心指令：
• 根据提示词生成10条差异化好评，每条不少于80字
• 必须包含：使用体验+具体优点+场景描述
• 禁止：重复内容、虚假评价、模糊表述
• 评价内容生成到comment字段，用户随机昵称生成到nickname字段，其余固定
• 生成3条评价
2. 评价维度（根据提示词自动分配）：
• 质量评价（占比40%）：材质/做工/耐用性
• 功能评价（占比30%）：实际使用效果
• 服务评价（占比20%）：物流/包装/售后
• 场景评价（占比10%）：特定使用场景体验
3. 特殊要求：
• 优先体现提示词相关内容
• 使用真实体验式口吻（如"收到后使用了半个月..."）
• 包含具体细节（如"缝线很密实""按键反应灵敏"）
• 每条评价需有明确侧重点
4. 输出格式：仅返回JSON，一条数据也按照多条数据的格式返回：
{
  [
    "product_id": ' . $data['product_id'] . ',
    "unique": ' . $data['unique'] . ',
    "store_name": ' . $productInfo['store_name'] . ',
    "product_image": ' . $productInfo['image'] . ',
    "delivery_score": 5,
    "service_score": 5,
    "product_score": 5,
    "reply_score": "3",
    "add_time": ' . $date . ',
    "nickname": "随机生成的用户名",
    "avatar": "在https://pro.crmeb.net/uploads/thumb_water/0e41012b7d7df9404ca456df1c7625c6.png,https://pro.crmeb.net/uploads/thumb_water/b0c0dc1d88c00870e16a2e21ca53b71a.png,https://pro.crmeb.net/uploads/thumb_water/037bb2585a07753bf9115d7f16bf5fe3.png,https://pro.crmeb.net/uploads/thumb_water/34dad9517ed6fcc7fdd4c035abad098c.png中随机选择一个",
    "comment": "评价内容"
  ]
  ...
}
';
        $userContent = '商品名称：' . $data['store_name'] . '，商品规格：' . $sku . '，提示词：' . $data['message'] . ', 评价维度：质量、功能、服务、场景';
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return json_decode($result, true);
    }

    public function community($data)
    {
        $systemContent = '
请严格按此规范执行：
1. 核心指令：
   • 生成3条内容
   • title标题要准确概括核心内容，不超过20个汉字，避免夸张表述
   • content内容要包含核心内容，内容的类型自适应（描述/心得/观点等），并且符合各平台规范，不少于200字，避免重复内容
   • topic_name话题名称要和内容相关，话题名称不超过10个汉字
   • 禁止使用网络流行语和过度口语化表达
   • 保持语言自然流畅，符合中文表达习惯
2. 内容要求：
   ▶ 标题规范：
   • 准确概括核心内容
   • 不超过20个汉字
   • 避免夸张表述
   ▶ 正文规范：
   • 首段：自然引入主题（50-80字）
   • 主体：根据内容类型自由展开
   • 结尾：恰当收束（30-50字）
3. 输出格式：仅返回JSON：
{
  [
    "title": "生成标题",
    "content": "生成正文内容",
    "topic_name": "话题名称",
  ]
   ...
}
4. 特殊要求：
   • 描述类：需包含五感细节（视觉/听觉/触觉等）
   • 心得类：需包含真实使用体验
   • 观点类：需包含合理逻辑推导
   • 禁止使用网络流行语和过度口语化表达
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return json_decode($result, true);
    }

    public function communityTitle($data)
    {
        $systemContent = ' 
请严格按此规范执行：  
1. 核心指令：  
   • 生成3条差异化标题，每条不超过20字，需准确概括文章内容
   • 必须包含：核心主题（前10字）+内容亮点+情感基调
   • 禁止：标题党、无关词、模糊表述（如"精彩""感人"）
2. 优化维度：  
   ▶ 标题1（流量型）：关键词聚焦+热点结合，例："三月樱花拍摄指南：手机出片的5个构图技巧"
   ▶ 标题2（共鸣型）：情感触动+场景代入，例："老城咖啡馆记事：藏在拿铁里的人生百态"
   ▶ 标题3（悬念型）：问题引发+价值提示，例："为何这间民宿让90%客人续住？丽江庭院设计秘密"
3. 输出格式：仅返回JSON：
{
  "t1": "标题1（关键词型）",
  "t2": "标题2（情感型）",
  "t3": "标题3（悬念型）"
} 
4. 特殊要求：  
   • 情感基调需明确（如"怀旧""治愈"而非"好看"）
   • 内容亮点需具体（如"手机摄影技巧""庭院设计细节"）
   • 文化类内容需标注地域/时期（如"唐代服饰""江南民居"）
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return array_values(json_decode($result, true));
    }

    public function communityContent($data)
    {
        $systemContent = ' 
请严格按此规范执行：  
1. 核心指令：  
   • 生成3条差异化优化版本，每条保持原篇幅±10%
   • 每条需体现不同优化方向（结构/语言/情感）
   • 禁止：改变核心事实、虚构内容
2. 优化维度：  
    ▶ 版本1（结构优化型）：
    重组段落逻辑
    增强过渡衔接
    突出核心论点
    ▶ 版本2（语言润色型）：
    提升表达精准度
    丰富修辞手法
    统一语言风格
    ▶ 版本3（情感强化型）：
    深化细节描写
    增强场景代入感
    把控情感节奏
3. 输出格式：仅返回JSON：
{
  "v1": "结构优化版本",
  "v2": "语言润色版本",
  "v3": "情感强化版本"
}
4. 特殊要求：  
   • 技术类：专业术语需验证
   • 文学类：保留原修辞特色
   • 商业类：符合广告法规定
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return array_values(json_decode($result, true));
    }

    public function articleTitle($data)
    {
        $systemContent = '
请严格按此规范执行：  
1. 核心指令：  
   • 生成3条差异化标题，每条不超过20字，需准确概括文章内容
   • 必须包含：核心主题（前10字）+内容亮点+情感基调
   • 禁止：标题党、无关词、模糊表述（如"精彩""感人"）
2. 优化维度：  
   ▶ 标题1（流量型）：关键词聚焦+热点结合，例："三月樱花拍摄指南：手机出片的5个构图技巧"
   ▶ 标题2（共鸣型）：情感触动+场景代入，例："老城咖啡馆记事：藏在拿铁里的人生百态"
   ▶ 标题3（悬念型）：问题引发+价值提示，例："为何这间民宿让90%客人续住？丽江庭院设计秘密"
3. 输出格式：仅返回JSON：
{
  "t1": "标题1（关键词型）",
  "t2": "标题2（情感型）",
  "t3": "标题3（悬念型）"
} 
4. 特殊要求：  
   • 情感基调需明确（如"怀旧""治愈"而非"好看"）
   • 内容亮点需具体（如"手机摄影技巧""庭院设计细节"）
   • 文化类内容需标注地域/时期（如"唐代服饰""江南民居"）
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return array_values(json_decode($result, true));
    }

    public function articleContent($data)
    {
        $systemContent = '
请严格按此规范执行：  
1. 核心指令：  
   • 生成3条差异化优化版本，每条保持原篇幅±10%
   • 每条需体现不同优化方向（结构/语言/情感）
   • 禁止：改变核心事实、虚构内容
2. 优化维度：  
    ▶ 版本1（结构优化型）：
    重组段落逻辑
    增强过渡衔接
    突出核心论点
    ▶ 版本2（语言润色型）：
    提升表达精准度
    丰富修辞手法
    统一语言风格
    ▶ 版本3（情感强化型）：
    深化细节描写
    增强场景代入感
    把控情感节奏
3. 输出格式：仅返回JSON：
{
  "v1": "结构优化版本",
  "v2": "语言润色版本",
  "v3": "情感强化版本"
}
4. 特殊要求：  
   • 技术类：专业术语需验证
   • 文学类：保留原修辞特色
   • 商业类：符合广告法规定
';
        $userContent = $data['message'];
        $result = $this->services->ai()->chat($systemContent, $userContent);
        return array_values(json_decode($result, true));
    }

}
