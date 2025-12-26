export const useBasePath = () => {
    // 解析路徑：移除 /add 或 /edit/[id] 後綴，取得基礎路徑
    // 用於在新增/編輯頁面中，取得基礎路徑
    const getBasePath = (path: string): string => {
        // 移除 /edit/[id] 部分
        const editMatch = path.match(/^(.+)\/edit\/\d+$/);
        if (editMatch && editMatch[1]) {
            return editMatch[1];
        }
        // 移除 /add 部分
        return path.replace("/add", "");
    };
    return { getBasePath };
};
