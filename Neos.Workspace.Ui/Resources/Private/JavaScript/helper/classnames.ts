export default function classnames(...classes): string {
    return classes.filter(Boolean).join(' ');
}
