import { atom } from "jotai";
import { ImperativePanelGroupHandle } from "react-resizable-panels";

// Default panel size (30% for the main content panel)
const DEFAULT_PANEL_SIZE = 0;

// Atom to store current panel sizes for reference
export const panelSizeAtom = atom<number>(DEFAULT_PANEL_SIZE);

// Atom to store reference to the panel group for programmatic control
export const panelRefAtom = atom<ImperativePanelGroupHandle | null>(null);

// Helper atom to get saved panel size from localStorage
export const getSavedPanelSizeAtom = atom<number>((get) => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('app-panel-size');
        return saved ? parseInt(saved, 10) : DEFAULT_PANEL_SIZE;
    }
    return DEFAULT_PANEL_SIZE;
});

// Action atom to set panel size using the ref
export const setPanelSizeAtom = atom(
    null,
    (get, set, size: number) => {
        const panelRef = get(panelRefAtom);
        if (panelRef) {
            // Set the layout: [main panel size, remaining space]
            panelRef.setLayout([size, 100 - size]);
            set(panelSizeAtom, size);
            
            // Persist to localStorage
            if (typeof window !== 'undefined') {
                localStorage.setItem('app-panel-size', size.toString());
            }
        }
    }
);
