/**
 * 前端状态常量映射表
 * 与后端 App\Enums 保持一致
 */

export const PaymentOrderStatus = {
  PENDING: 'pending',
  SUCCESS: 'success',
  FAILED: 'failed',
  CLOSED: 'closed',

  label: (s) => ({ pending: '处理中', success: '成功', failed: '失败', closed: '已退款' }[s] || s),
  color: (s) => ({ success: 'success', pending: 'warning', failed: 'danger', closed: 'gray' }[s] || 'gray'),
}

export const NotifyStatus = {
  PENDING: 'pending',
  SUCCESS: 'success',
  FAILED: 'failed',

  label: (s) => ({ pending: '待发货', success: '已发货', failed: '发货失败' }[s] || s),
  color: (s) => ({ success: 'success', pending: 'warning', failed: 'danger' }[s] || 'gray'),
}

export const GameServerStatus = {
  HOT: 1,
  RECOMMEND: 2,
  MAINTENANCE: 3,
  FULL: 4,

  label: (s) => ({ 1: '火爆', 2: '推荐', 3: '维护中', 4: '已满' }[s] || '未知'),
  badgeClass: (s) => ({
    1: 'text-green-600 bg-green-50',
    2: 'text-orange-600 bg-orange-50',
    3: 'text-gray-400 bg-gray-100',
    4: 'text-red-600 bg-red-50',
  }[s] || 'text-gray-500 bg-gray-50'),
}

export const GameServerDynamicStatus = (server) => {
  const now = Date.now()
  const openTime = new Date(server.open_time).getTime()
  const diffDays = (openTime - now) / (1000 * 60 * 60 * 24)

  if (server.status === 3) return { label: '维护中', color: 'text-gray-400 bg-gray-100' }
  if (server.status === 4) return { label: '已满', color: 'text-red-600 bg-red-50' }
  if (diffDays > 3) return { label: '未开服', color: 'text-gray-500 bg-gray-100' }
  if (diffDays > 0) return { label: '即将开服', color: 'text-blue-600 bg-blue-50' }
  if (server.status === 1) return { label: '火爆', color: 'text-green-600 bg-green-50' }
  if (server.is_recommend || server.status === 2) return { label: '推荐', color: 'text-orange-600 bg-orange-50' }
  return { label: '已开服', color: 'text-gray-600 bg-gray-100' }
}

export const NoticeType = {
  label: (t) => ({ platform: '平台公告', game: '游戏公告', maintenance: '维护公告', activity: '活动公告', merge: '合服公告' }[t] || t),
  color: (t) => ({
    platform: 'bg-gray-100 text-gray-700', game: 'bg-blue-100 text-blue-700',
    maintenance: 'bg-red-100 text-red-700', activity: 'bg-green-100 text-green-700',
    merge: 'bg-purple-100 text-purple-700',
  }[t] || 'bg-gray-100 text-gray-700'),
}

export const RecommendPosition = {
  label: (c) => ({ banner: '首页轮播', hot: '热门推荐', new: '新游上线', featured: '精品游戏' }[c] || c),
}
