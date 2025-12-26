/**
 * 用於解析 URL 並取得對應的 structure_id 和模組資訊
 */
export const useStructureResolver = () => {
    const { data: structureData } = useStructure();
    const { data: modulesData } = useModule();
    const route = useRoute();

    /**
     * 根據 URL 路徑找到對應的系統架構項目
     * @param path URL 路徑，例如 '/contact' 或 '/custom-url'
     * @returns 找到的系統架構項目，包含 id, module_id, url 等資訊
     */
    const findStructureByPath = (path: string): any | null => {
        if (!path || !structureData.value || !modulesData.value) {
            return null;
        }

        // 移除開頭的斜線
        const normalizedPath = path.startsWith("/") ? path.slice(1) : path;

        // 遞迴搜尋結構樹
        const searchInTree = (items: any[]): any | null => {
            for (const item of items) {
                // 檢查是否有自訂 URL
                if (item.url) {
                    const itemUrl = item.url.startsWith("/")
                        ? item.url.slice(1)
                        : item.url;
                    if (itemUrl === normalizedPath) {
                        return item;
                    }
                }

                // 如果沒有自訂 URL，檢查模組的 name
                if (item.module_id && !item.url) {
                    const module = modulesData.value.find(
                        (m: any) => String(m.id) === String(item.module_id)
                    );
                    if (module?.name) {
                        const modulePath = module.name.startsWith("/")
                            ? module.name.slice(1)
                            : module.name;
                        if (modulePath === normalizedPath) {
                            return item;
                        }
                    }
                }

                // 遞迴搜尋子項目
                if (item.children && item.children.length > 0) {
                    const found = searchInTree(item.children);
                    if (found) {
                        return found;
                    }
                }
            }
            return null;
        };

        return searchInTree(structureData.value);
    };

    /**
     * 取得當前路由對應的 structure_id
     * @returns structure_id 或 null
     */
    const getCurrentStructureId = (): number | null => {
        const structure = findStructureByPath(route.path);
        return structure?.id ? Number(structure.id) : null;
    };

    /**
     * 根據路徑取得 structure_id
     * @param path URL 路徑
     * @returns structure_id 或 null
     */
    const getStructureIdByPath = (path: string): number | null => {
        const structure = findStructureByPath(path);
        return structure?.id ? Number(structure.id) : null;
    };

    /**
     * 取得當前路由對應的完整結構資訊
     * @returns 結構資訊或 null
     */
    const getCurrentStructure = (): any | null => {
        return findStructureByPath(route.path);
    };

    /**
     * 解析 URL 並取得模組資訊
     * @param path URL 路徑
     * @returns 包含 structure_id, module_id, module_name 等資訊的物件
     */
    const resolvePath = (
        path: string
    ): {
        structure_id: number | null;
        module_id: number | null;
        module_name: string | null;
        structure: any | null;
    } => {
        const structure = findStructureByPath(path);

        if (!structure) {
            return {
                structure_id: null,
                module_id: null,
                module_name: null,
                structure: null
            };
        }

        const module = structure.module_id
            ? modulesData.value?.find(
                  (m: any) => String(m.id) === String(structure.module_id)
              )
            : null;

        return {
            structure_id: structure.id ? Number(structure.id) : null,
            module_id: structure.module_id ? Number(structure.module_id) : null,
            module_name: module?.name || null,
            structure: structure
        };
    };

    return {
        findStructureByPath,
        getCurrentStructureId,
        getStructureIdByPath,
        getCurrentStructure,
        resolvePath
    };
};
