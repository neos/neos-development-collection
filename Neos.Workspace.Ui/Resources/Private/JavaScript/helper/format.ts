export function formatDate(datetime: string | number): string {
    return new Date(datetime).toLocaleString(undefined, {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}
