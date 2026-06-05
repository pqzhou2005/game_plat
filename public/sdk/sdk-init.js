/**
 * 602游戏平台 - 游戏接入SDK
 * 游戏方在游戏内引用此文件，通过 postMessage 与平台通信
 *
 * 用法:
 *   SDK.pay({...})       - 调起支付
 *   SDK.role({...})      - 角色上报
 *   SDK.batchRole([...]) - 批量角色上报
 */
(function (global) {
  'use strict'

  function isInIframe() {
    try {
      return global.self !== global.top
    } catch (e) {
      return true
    }
  }

  function postMessage(type, data) {
    if (isInIframe()) {
      global.parent.postMessage({ type: type, data: data }, '*')
    } else {
      console.warn('[602SDK] 不在iframe中，无法通信')
    }
  }

  var SDK = {
    pay: function (params) {
      postMessage('pay', params)
    },

    role: function (params) {
      postMessage('role', params)
    },

    batchRole: function (paramsArray) {
      postMessage('batch_role', paramsArray)
    },
  }

  global.SDK = SDK
})(window)
