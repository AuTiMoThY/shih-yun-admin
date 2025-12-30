import type { CutSectionData } from "~/types/CutSectionField";
import { useDateFormat, useNow } from "@vueuse/core";

export const useAppAbout = () => {
    const { public: runtimePublic } = useRuntimeConfig();
    const apiBase = runtimePublic.apiBase;
    const toast = useToast();

    const buildId = ref(useDateFormat(useNow(), "YYYYMMDDHHmmss"));
    const sections = ref<CutSectionData[]>([]);
    const title = ref<string | null>(null);
    const loading = ref(false);
    const submitError = ref<string>("");

    const fetchData = async (structureId?: number | null) => {
        loading.value = true;
        submitError.value = "";
        try {
            const queryParams = new URLSearchParams();
            if (structureId !== undefined && structureId !== null) {
                queryParams.append("structure_id", String(structureId));
            }
            const queryString = queryParams.toString();
            const url = `${apiBase}/app-about/get${
                queryString ? `?${queryString}` : ""
            }`;

            // console.log("[useAbout] fetchData: 開始載入資料", {
            //     apiBase,
            //     url,
            //     structureId
            // });

            const res = await $fetch<{
                success: boolean;
                data?: { title?: string | null; sections?: CutSectionData[] };
                message?: string;
                error?: string;
                debug?: any;
            }>(url);

            // console.log("[useAbout] fetchData: API 回應", {
            //     success: res?.success,
            //     hasData: !!res?.data,
            //     sectionsCount: res?.data?.sections?.length ?? 0,
            //     message: res?.message,
            //     error: res?.error,
            //     debug: res?.debug
            // });

            if (res?.success && res.data) {
                title.value = res.data.title ?? null;
                sections.value = res.data.sections ?? [];
                // console.log("[useAbout] fetchData: 資料載入成功", {
                //     title: title.value,
                //     sectionsCount: sections.value.length
                // });
            } else {
                const errorMsg = res?.message || res?.error || "載入失敗";
                submitError.value = errorMsg;
                // console.error("[useAbout] fetchData: API 回應失敗", {
                //     res,
                //     errorMsg
                // });

                toast.add({
                    title: "載入失敗",
                    description: errorMsg,
                    color: "error"
                });
            }
        } catch (error: any) {
            const errorMsg =
                error?.message ||
                error?.data?.message ||
                error?.data?.error ||
                "載入時發生錯誤";
            submitError.value = errorMsg;

            // console.error("[useAbout] fetchData: 發生異常", {
            //     error,
            //     message: error?.message,
            //     statusCode: error?.statusCode,
            //     statusMessage: error?.statusMessage,
            //     data: error?.data,
            //     stack: error?.stack
            // });

            toast.add({
                title: "載入失敗",
                description:
                    errorMsg +
                    (error?.data?.debug
                        ? ` (${error.data.debug.file}:${error.data.debug.line})`
                        : ""),
                color: "error"
            });
        } finally {
            loading.value = false;
        }
    };

    const saveAbout = async (structureId?: number | null) => {
        loading.value = true;
        submitError.value = "";
        try {
            const payload = {
                title: title.value,
                sections: sections.value,
                ...(structureId !== undefined && structureId !== null
                    ? { structure_id: structureId }
                    : {})
            };

            // console.log("[useAbout] saveAbout: 開始儲存資料", {
            //     apiBase,
            //     url: `${apiBase}/app-about/save`,
            //     payload: {
            //         title: payload.title,
            //         sectionsCount: payload.sections.length,
            //         sections: payload.sections
            //     }
            // });

            const res = await $fetch<{
                success: boolean;
                message?: string;
                error?: string;
                debug?: any;
                model_errors?: any;
            }>(`${apiBase}/app-about/save`, {
                method: "POST",
                body: payload
            });

            // console.log("[useAbout] saveAbout: API 回應", {
            //     success: res?.success,
            //     message: res?.message,
            //     error: res?.error,
            //     debug: res?.debug,
            //     model_errors: res?.model_errors
            // });

            if (res.success) {
                toast.add({
                    title: "已儲存",
                    description: "關於我們內容已更新",
                    color: "success"
                });
                // console.log("[useAbout] saveAbout: 儲存成功，重新載入資料", {
                //     structureId
                // });
                await fetchData(structureId);
                return true;
            } else {
                const msg = res?.message || res?.error || "儲存失敗";
                submitError.value = msg;

                let description = msg;
                if (res?.model_errors) {
                    description += ` (Model errors: ${JSON.stringify(
                        res.model_errors
                    )})`;
                }
                if (res?.debug) {
                    description += ` (${res.debug.file}:${res.debug.line})`;
                }

                // console.error("[useAbout] saveAbout: API 回應失敗", {
                //     res,
                //     msg
                // });

                toast.add({
                    title: "儲存失敗",
                    description,
                    color: "error"
                });
            }
        } catch (error: any) {
            const errorMsg =
                error?.message ||
                error?.data?.message ||
                error?.data?.error ||
                "儲存時發生錯誤";
            submitError.value = errorMsg;

            console.error("[useAbout] saveAbout: 發生異常", {
                error,
                message: error?.message,
                statusCode: error?.statusCode,
                statusMessage: error?.statusMessage,
                data: error?.data,
                stack: error?.stack
            });

            let description = errorMsg;
            if (error?.data?.debug) {
                description += ` (${error.data.debug.file}:${error.data.debug.line})`;
            }
            if (error?.data?.model_errors) {
                description += ` (Model errors: ${JSON.stringify(
                    error.data.model_errors
                )})`;
            }

            toast.add({
                title: "儲存失敗",
                description,
                color: "error"
            });
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
