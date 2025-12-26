export const useValidateFormErrorMsg = (errors: any) => {
    const errorMessages = Object.values(errors)
        .filter((error): error is string => typeof error === "string" && error !== "")
        .join("、");
    return errorMessages || "表單驗證失敗";
}