<template>
  <view class="gift-message-card">
    <view class="message-header">
      <image class="avatar" :src="avatar" mode="aspectFill"></image>
      <text class="gift-text">{{ nickname }} 赠您一份礼物，请查收！</text>
    </view>
    <view class="divider"></view>
    <view class="message-content">
      <text class="message-text">{{
        gift_mark || "天天开心，所得皆所愿~"
      }}</text>
    </view>
  </view>
</template>

<script>
export default {
  name: "SendGiftMarkCard",
  props: {
    avatar: {
      type: String,
      default: "",
    },
    nickname: {
      type: String,
      default: "匿名",
    },
    gift_mark: {
      type: String,
      default: "",
    },
  },
};
</script>

<style lang="scss" scoped>
.gift-message-card {
  background-color: #ffffff;
  border-radius: 24rpx;
  padding: 32rpx 24rpx;
  .message-header {
    display: flex;
    align-items: center;
    margin-bottom: 20rpx;

    .avatar {
      width: 36rpx;
      height: 36rpx;
      border-radius: 50%;
      margin-right: 16rpx;
    }

    .gift-text {
      font-size: 28rpx;
      color: #ae5a2a;
      font-weight: 400;
    }
  }

  .divider {
    height: 1rpx;
    background-color: #f5f5f5;
    margin: 20rpx 0;
  }

  .message-content {
    .message-text {
      font-size: 28rpx;
      color: #333;
      line-height: 1.5;
    }
  }
}
</style>
