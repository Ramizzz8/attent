import { Paperclip } from 'lucide-react';

/** Botón de adjuntar con el mismo texto en todos los navegadores (el control nativo sale en el idioma del navegador). */
export function SelectorArchivos({
    id,
    archivos,
    onChange,
    texto = 'Adjuntar archivos',
    accept = '.jpg,.jpeg,.png,.webp,.pdf',
    multiple = true,
}: {
    id: string;
    archivos: File[];
    onChange: (archivos: File[]) => void;
    texto?: string;
    accept?: string;
    multiple?: boolean;
}) {
    return (
        <div className="flex min-w-0 flex-wrap items-center gap-3">
            <label
                htmlFor={id}
                className="inline-flex h-9 cursor-pointer items-center gap-2 rounded-md border px-3 text-sm font-medium shadow-xs transition hover:bg-muted has-[:focus-visible]:ring-[3px] has-[:focus-visible]:ring-ring/50"
            >
                <Paperclip className="size-4" aria-hidden="true" />
                {texto}
                <input
                    id={id}
                    type="file"
                    multiple={multiple}
                    accept={accept}
                    className="sr-only"
                    onChange={(e) => {
                        onChange(Array.from(e.target.files ?? []));
                        e.target.value = '';
                    }}
                />
            </label>
            <span className="min-w-0 truncate text-sm text-muted-foreground" aria-live="polite">
                {archivos.length === 0 ? 'Ningún archivo' : archivos.length === 1 ? archivos[0].name : `${archivos.length} archivos`}
            </span>
        </div>
    );
}
