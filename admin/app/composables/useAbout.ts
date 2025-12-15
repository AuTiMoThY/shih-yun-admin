import type { CutSectionData } from "~/types/CutSectionField";
import { useDateFormat, useNow } from "@vueuse/core";


export const useAbout = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();

    const buildId = ref(useDateFormat(useNow(), "YYYYMMDDHHmmss"));
    const sections = ref<CutSectionData[]>([]);
    const title = ref<string | null>(null);
    const loading = ref(false);
    const submitError = ref<string>("");


    const fetchData = async () => {
        loading.value = true;
        submitError.value = "";
        try {
            const res = await $fetch<{
                success: boolean;
                data?: { title?: string | null; sections?: CutSectionData[] };
                message?: string;
            }>(`${apiBase}/appabout/get`);

            if (res?.success && res.data) {
                title.value = res.data.title ?? null;
                sections.value = res.data.sections ?? [];
            } else {
                submitError.value = res?.message || "載入失敗";
            }
        } catch (error: any) {
            submitError.value = error?.message || "載入時發生錯誤";
            console.error(error);
        } finally {
            loading.value = false;
        }
    };

    const saveAbout = async () => {
        loading.value = true;
        submitError.value = "";
        try {
            const res = await $fetch<{ success: boolean; message?: string }>(
                `${apiBase}/appabout/save`,
                {
                    method: "POST",
                    body: {
                        title: title.value,
                        sections: sections.value
                    }
                }
            );

            if (res.success) {
                toast.add({
                    title: "已儲存",
                    description: "關於我們內容已更新",
                    color: "success"
                });
                await fetchData();
                return true;
            } else {
                const msg = res?.message || "儲存失敗";
                submitError.value = msg;
                toast.add({
                    title: "儲存失敗",
                    description: msg,
                    color: "error"
                });
            }
        } catch (error: any) {
            submitError.value = error?.message || "儲存時發生錯誤";
            toast.add({
                title: "儲存失敗",
                description: submitError.value,
                color: "error"
            });
            console.error(error);
        } finally {
            loading.value = false;
        }
    };

    return {
        buildId,
        title,
        sections,
        loading,
        submitError,
        fetchData,
        saveAbout
    };
};
