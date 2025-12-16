<script setup lang="ts">
/**
 * 權限守衛元件
 * 根據權限決定是否顯示內容
 * 
 * 使用範例：
 * <PermissionGuard permission="product.view">
 *   <div>只有有權限的使用者才能看到這個內容</div>
 * </PermissionGuard>
 * 
 * <PermissionGuard :permissions="['product.view', 'product.edit']" require-all>
 *   <div>需要同時擁有兩個權限</div>
 * </PermissionGuard>
 * 
 * <PermissionGuard role="super_admin">
 *   <div>只有超級管理員能看到</div>
 * </PermissionGuard>
 */

interface Props {
  // 單一權限
  permission?: string | undefined
  // 多個權限
  permissions?: string[] | undefined
  // 是否需要全部權限 (預設 false = 任一即可)
  requireAll?: boolean
  // 角色檢查
  role?: string | undefined
  // 多個角色
  roles?: string[] | undefined
  // 是否需要全部角色
  requireAllRoles?: boolean
  // 無權限時顯示的內容
  fallback?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  permission: undefined,
  permissions: undefined,
  requireAll: false,
  role: undefined,
  roles: undefined,
  requireAllRoles: false,
  fallback: false,
})

const permissionHelper = usePermission()

const hasAccess = computed(() => {
  // 超級管理員擁有所有權限
  if (permissionHelper.isSuperAdmin()) {
    return true
  }

  // 檢查權限
  if (props.permission) {
    return permissionHelper.hasPermission(props.permission)
  }

  if (props.permissions && props.permissions.length > 0) {
    if (props.requireAll) {
      return permissionHelper.hasAllPermissions(props.permissions)
    } else {
      return permissionHelper.hasAnyPermission(props.permissions)
    }
  }

  // 檢查角色
  if (props.role) {
    return permissionHelper.hasRole(props.role)
  }

  if (props.roles && props.roles.length > 0) {
    if (props.requireAllRoles) {
      return permissionHelper.hasAllRoles(props.roles)
    } else {
      return permissionHelper.hasAnyRole(props.roles)
    }
  }

  // 沒有指定任何條件，預設顯示
  return true
})
</script>

<template>
  <div v-if="hasAccess">
    <slot />
  </div>
  <div v-else-if="fallback">
    <slot name="fallback">
      <UAlert
        title="沒有權限"
        description="您沒有權限查看此內容"
        color="error"
        variant="soft"
        icon="i-lucide-shield-alert"
      />
    </slot>
  </div>
</template>

