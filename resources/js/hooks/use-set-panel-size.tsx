import { useSetAtom } from "jotai";
import { setPanelSizeAtom } from "@/atoms/panel-atom";
import { useEffect } from "react";

/**
 * Hook to programmatically set the panel size globally
 * @param size - The size percentage for the main panel (0-100)
 */
export function useSetPanelSize(size: number) {
    const setPanelSize = useSetAtom(setPanelSizeAtom);

    useEffect(() => {
        if (size >= 0 && size <= 100) {
            setPanelSize(size);
        }
    }, [size, setPanelSize]);
}

/**
 * Hook that returns a function to set panel size imperatively
 * @returns A function that takes a size parameter and sets the panel size
 */
export function usePanelSizeControl() {
    const setPanelSize = useSetAtom(setPanelSizeAtom);
    
    return (size: number) => {
        if (size >= 0 && size <= 100) {
            setPanelSize(size);
        }
    };
}
