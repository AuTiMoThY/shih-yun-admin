// 明確導入組件以確保能夠正確解析
import AppAbout from "~/components/App/About/AppAbout.vue";
import AppNews from "~/components/App/News/AppNews.vue";
import AppContact from "~/components/App/Contact/AppContact.vue";
import AppNewsFormPage from "~/components/App/News/FormPage.vue";
import AppContactFormPage from "~/components/App/Contact/FormPage.vue";

export const useModuleComponent = () => {
    /**
     * 模組名稱與組件的映射關係
     * module_name -> component 名稱
     */
    const moduleComponentMap: Record<string, any> = {
        about: AppAbout,
        news: AppNews,
        contact: AppContact
    };

    /**
     * 模組名稱與表單組件的映射關係
     * module_name -> form component 名稱
     */
    const moduleFormComponentMap: Record<string, any> = {
        news: AppNewsFormPage,
        contact: AppContactFormPage
    };

    /**
     * 根據模組名稱取得對應的組件名稱
     * @param moduleName 模組名稱（例如：'about', 'news', 'contact'）
     * @returns 組件名稱（例如：'AppAbout', 'AppNews', 'AppContact'）或 null
     */
    const getComponentByModule = (moduleName: string | null): string | null => {
        if (!moduleName) {
            return null;
        }
        return moduleComponentMap[moduleName] || null;
    };

    /**
     * 檢查模組是否有對應的組件
     * @param moduleName 模組名稱
     * @returns 是否有對應的組件
     */
    const hasComponentForModule = (moduleName: string | null): boolean => {
        return getComponentByModule(moduleName) !== null;
    };

    /**
     * 取得所有已註冊的模組名稱
     * @returns 模組名稱陣列
     */
    const getRegisteredModules = (): string[] => {
        return Object.keys(moduleComponentMap);
    };

    /**
     * 根據模組名稱取得對應的表單組件
     * @param moduleName 模組名稱（例如：'news', 'contact'）
     * @returns 表單組件或 null
     */
    const getFormComponentByModule = (moduleName: string | null): any | null => {
        if (!moduleName) {
            return null;
        }
        return moduleFormComponentMap[moduleName] || null;
    };

    /**
     * 檢查模組是否有對應的表單組件
     * @param moduleName 模組名稱
     * @returns 是否有對應的表單組件
     */
    const hasFormComponentForModule = (moduleName: string | null): boolean => {
        return getFormComponentByModule(moduleName) !== null;
    };

    return {
        getComponentByModule,
        hasComponentForModule,
        getRegisteredModules,
        getFormComponentByModule,
        hasFormComponentForModule
    };
};
