import type { Permission, Role } from '~/types'

/**
 * 權限檢查 Composable
 * 提供各種權限和角色檢查方法
 */
export const usePermission = () => {
  const auth = useAuth()
  const user = computed(() => auth.user.value)

  /**
   * 獲取使用者的所有權限
   */
  const getUserPermissions = (): string[] => {
    if (!user.value) return []
    
    const permissions: string[] = []
    
    // 從角色獲取權限
    if (user.value.roles) {
      user.value.roles.forEach((role: Role) => {
        if (role.permissions) {
          role.permissions.forEach((permission: Permission) => {
            if (!permissions.includes(permission.name)) {
              permissions.push(permission.name)
            }
          })
        }
      })
    }
    
    // 加入直接授予的權限
    // 注意：後端返回的 user.value.permissions 是字串陣列（權限名稱），不是物件陣列
    if (user.value.permissions) {
      user.value.permissions.forEach((permission: string | Permission) => {
        // 判斷是字串還是物件
        const permissionName = typeof permission === 'string' ? permission : permission.name
        if (permissionName && !permissions.includes(permissionName)) {
          permissions.push(permissionName)
        }
      })
    }
    
    return permissions
  }

  /**
   * 獲取使用者的所有角色
   */
  const getUserRoles = (): string[] => {
    if (!user.value?.roles) return []
    return user.value.roles.map((role: Role) => role.name)
  }

  /**
   * 檢查是否有指定權限
   * @param permission 權限名稱或權限名稱陣列
   */
  const hasPermission = (permission: string | string[]): boolean => {
    const userPermissions = getUserPermissions()
    
    if (Array.isArray(permission)) {
      return permission.every(p => userPermissions.includes(p))
    }
    
    return userPermissions.includes(permission)
  }

  /**
   * 檢查是否有任一權限
   * @param permissions 權限名稱陣列
   */
  const hasAnyPermission = (permissions: string[]): boolean => {
    const userPermissions = getUserPermissions()
    return permissions.some(p => userPermissions.includes(p))
  }

  /**
   * 檢查是否有所有權限
   * @param permissions 權限名稱陣列
   */
  const hasAllPermissions = (permissions: string[]): boolean => {
    const userPermissions = getUserPermissions()
    return permissions.every(p => userPermissions.includes(p))
  }

  /**
   * 檢查是否有指定角色
   * @param role 角色名稱或角色名稱陣列
   */
  const hasRole = (role: string | string[]): boolean => {
    const userRoles = getUserRoles()
    
    if (Array.isArray(role)) {
      return role.every(r => userRoles.includes(r))
    }
    
    return userRoles.includes(role)
  }

  /**
   * 檢查是否有任一角色
   * @param roles 角色名稱陣列
   */
  const hasAnyRole = (roles: string[]): boolean => {
    const userRoles = getUserRoles()
    return roles.some(r => userRoles.includes(r))
  }

  /**
   * 檢查是否有所有角色
   * @param roles 角色名稱陣列
   */
  const hasAllRoles = (roles: string[]): boolean => {
    const userRoles = getUserRoles()
    return roles.every(r => userRoles.includes(r))
  }

  /**
   * 檢查是否為超級管理員
   */
  const isSuperAdmin = (): boolean => {
    return hasRole('super_admin')
  }

  /**
   * 檢查模組權限
   * @param module 模組名稱 (如: product, finance, member)
   * @param action 動作 (如: view, create, edit, delete)
   * @param category 分類 (如: tw, sg, mm) - 可選
   */
  const hasModulePermission = (
    module: string,
    action: string,
    category?: string
  ): boolean => {
    // 超級管理員有所有權限
    if (isSuperAdmin()) return true

    // 構建權限名稱
    const permissionName = category
      ? `${module}.${category}.${action}`
      : `${module}.${action}`

    return hasPermission(permissionName)
  }

  /**
   * 檢查是否有模組的任何權限
   * @param module 模組名稱
   */
  const hasAnyModulePermission = (module: string): boolean => {
    const userPermissions = getUserPermissions()
    return userPermissions.some(p => p.startsWith(`${module}.`))
  }

  /**
   * 獲取使用者在特定模組的權限
   * @param module 模組名稱
   */
  const getModulePermissions = (module: string): string[] => {
    const userPermissions = getUserPermissions()
    return userPermissions.filter(p => p.startsWith(`${module}.`))
  }

  /**
   * 檢查區域權限 (台灣/新加坡/緬甸)
   * @param module 模組名稱
   * @param region 區域代碼 (tw, sg, mm)
   * @param action 動作
   */
  const hasRegionPermission = (
    module: string,
    region: 'tw' | 'sg' | 'mm',
    action: string = 'view'
  ): boolean => {
    // 超級管理員有所有權限
    if (isSuperAdmin()) return true

    // 檢查特定區域權限
    const specificPermission = `${module}.${region}.${action}`
    if (hasPermission(specificPermission)) return true

    // 檢查通用管理權限
    const managePermission = `${module}.${region}.manage`
    return hasPermission(managePermission)
  }

  /**
   * 獲取使用者可訪問的區域
   * @param module 模組名稱
   */
  const getAccessibleRegions = (module: string): ('tw' | 'sg' | 'mm')[] => {
    const regions: ('tw' | 'sg' | 'mm')[] = []
    const userPermissions = getUserPermissions()

    ;['tw', 'sg', 'mm'].forEach((region) => {
      const hasRegionPerm = userPermissions.some(
        p => p.startsWith(`${module}.${region}.`) || 
             p === `${module}.${region}`
      )
      if (hasRegionPerm) {
        regions.push(region as 'tw' | 'sg' | 'mm')
      }
    })

    return regions
  }

  return {
    // 基本檢查
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
    hasRole,
    hasAnyRole,
    hasAllRoles,
    isSuperAdmin,

    // 模組檢查
    hasModulePermission,
    hasAnyModulePermission,
    getModulePermissions,

    // 區域檢查
    hasRegionPermission,
    getAccessibleRegions,

    // 獲取資訊
    getUserPermissions,
    getUserRoles,
  }
}

