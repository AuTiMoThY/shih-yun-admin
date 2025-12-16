export default defineNuxtPlugin((nuxtApp) => {
    const { hasPermission, hasRole, isSuperAdmin } = usePermission();

    /**
     * 檢查權限
     */
    const checkPermission = (binding: any): boolean => {
        const permission = binding.value;
        if (!permission) {
            return false;
        }

        // 超級管理員有所有權限
        if (isSuperAdmin()) {
            return true;
        }

        // 檢查權限（支援字串或陣列）
        if (Array.isArray(permission)) {
            return permission.every(p => hasPermission(p));
        }

        return hasPermission(permission);
    };

    /**
     * 檢查角色
     */
    const checkRole = (binding: any): boolean => {
        const role = binding.value;
        if (!role) {
            return false;
        }

        // 檢查角色（支援字串或陣列）
        if (Array.isArray(role)) {
            return role.every(r => hasRole(r));
        }

        return hasRole(role);
    };

    /**
     * 移除元素（從 DOM 中完全移除，類似 v-if="false"）
     */
    const removeElement = (el: HTMLElement) => {
        // 如果已經被移除（是 comment node），則不處理
        if (el.nodeType === Node.COMMENT_NODE) {
            return;
        }

        // 儲存原始元素和位置資訊
        const parent = el.parentNode;
        const nextSibling = el.nextSibling;
        
        if (parent) {
            // 建立 comment node 作為佔位符
            const comment = document.createComment(' v-permission: hidden ');
            (comment as any).__originalElement = el;
            (comment as any).__originalNextSibling = nextSibling;
            
            // 用 comment node 替換元素
            parent.replaceChild(comment, el);
        }
    };

    /**
     * 恢復元素（將元素重新插入 DOM，類似 v-if="true"）
     */
    const restoreElement = (node: Node): HTMLElement | null => {
        // 如果不是 comment node，直接返回
        if (node.nodeType !== Node.COMMENT_NODE) {
            return node as HTMLElement;
        }

        const comment = node as unknown as Comment;
        const originalElement = (comment as any).__originalElement as HTMLElement;
        
        if (!originalElement || !comment.parentNode) {
            return null;
        }

        // 恢復元素到原來的位置
        const nextSibling = (comment as any).__originalNextSibling;
        if (nextSibling && nextSibling.parentNode === comment.parentNode) {
            comment.parentNode.insertBefore(originalElement, nextSibling);
        } else {
            comment.parentNode.replaceChild(originalElement, comment);
        }

        return originalElement;
    };

    // v-permission 指令：檢查權限
    nuxtApp.vueApp.directive('permission', {
        mounted(el: HTMLElement, binding) {
            const hasAccess = checkPermission(binding);
            if (!hasAccess) {
                removeElement(el);
            }
        },
        updated(el: HTMLElement, binding) {
            // 如果當前是 comment node，先恢復元素
            let currentElement: HTMLElement | null = el as HTMLElement;
            if (el.nodeType === Node.COMMENT_NODE) {
                currentElement = restoreElement(el);
                if (!currentElement) {
                    return;
                }
            }

            // 檢查權限
            const hasAccess = checkPermission(binding);
            if (!hasAccess) {
                // 沒有權限，移除元素
                if (currentElement && currentElement.nodeType !== Node.COMMENT_NODE) {
                    removeElement(currentElement);
                }
            }
            // 如果有權限，元素已經在 DOM 中，保持顯示
        },
        beforeUnmount(el: HTMLElement) {
            // 清理引用
            if (el.nodeType === Node.COMMENT_NODE) {
                const comment = el as unknown as Comment;
                if ((comment as any).__originalElement) {
                    delete (comment as any).__originalElement;
                    delete (comment as any).__originalNextSibling;
                }
            } else {
                // 確保元素被正確清理
                if ((el as any).__originalElement) {
                    delete (el as any).__originalElement;
                }
            }
        }
    });

    // v-role 指令：檢查角色
    nuxtApp.vueApp.directive('role', {
        mounted(el: HTMLElement, binding) {
            const hasAccess = checkRole(binding);
            if (!hasAccess) {
                removeElement(el);
            }
        },
        updated(el: HTMLElement, binding) {
            // 如果當前是 comment node，先恢復元素
            let currentElement: HTMLElement | null = el as HTMLElement;
            if (el.nodeType === Node.COMMENT_NODE) {
                currentElement = restoreElement(el);
                if (!currentElement) {
                    return;
                }
            }

            // 檢查角色
            const hasAccess = checkRole(binding);
            if (!hasAccess) {
                // 沒有權限，移除元素
                if (currentElement && currentElement.nodeType !== Node.COMMENT_NODE) {
                    removeElement(currentElement);
                }
            }
            // 如果有權限，元素已經在 DOM 中，保持顯示
        },
        beforeUnmount(el: HTMLElement) {
            // 清理引用
            if (el.nodeType === Node.COMMENT_NODE) {
                const comment = el as unknown as Comment;
                if ((comment as any).__originalElement) {
                    delete (comment as any).__originalElement;
                    delete (comment as any).__originalNextSibling;
                }
            } else {
                // 確保元素被正確清理
                if ((el as any).__originalElement) {
                    delete (el as any).__originalElement;
                }
            }
        }
    });
});
